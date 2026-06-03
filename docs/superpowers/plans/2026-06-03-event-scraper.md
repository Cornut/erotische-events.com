# Event Scraper Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use `- [ ]`.

**Goal:** A generic, cron-able event-scraping engine that imports events for all non-rejected organizers (and a single one on demand), using a structured-data extractor first and an Anthropic-Claude LLM fallback, normalizing prices to EUR and publishing the events.

**Architecture:** `app/Scraping/` subsystem. `EventScraperService` orchestrates per organizer: `EventsUrlResolver` → `PageFetcher` → ordered `EventExtractor` chain (`StructuredDataExtractor`, then `LlmEventExtractor` behind an `LlmClient`) → `EventImportService` upserts `events` (status published, `source_url`, primary venue, organizer category, EUR prices). CLI `events:scrape {--organizer=}` + daily scheduler.

**Tech Stack:** Laravel 13, Pest, Laravel HTTP client, Anthropic API (Claude) behind an interface, sqlite :memory: tests. 111 tests currently pass on `main`.

**Conventions:**
- Repo root `/Users/comodo/Documents/sites/erotische-events.com/root`; run commands there. Branch created by the execution skill.
- Spec: `docs/superpowers/specs/2026-06-03-event-scraper-design.md`.
- TDD per task; after each: `php artisan test` green + `vendor/bin/pint --test` clean; commit listed files.
- NO live network in tests: HTTP behind `PageFetcher`, LLM behind `LlmClient`; both faked in tests. The events table/Event model, Organizer (with `category`, primary venue, `imageDirectory()`), Venue, EventPrice, EventStatus already exist.

---

### Task 1: Scraping config + Anthropic service config

**Files:**
- Create: `config/scraping.php`
- Modify: `config/services.php`, `.env.example`
- Test: `tests/Feature/Scraping/ScrapingConfigTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/Scraping/ScrapingConfigTest.php`:

```php
<?php

it('exposes scraping config defaults', function () {
    expect(config('scraping.candidate_paths'))->toContain('/termine', '/seminare')
        ->and(config('scraping.fx.CHF'))->toBeGreaterThan(0)
        ->and(config('scraping.timeout'))->toBeInt()
        ->and(config('services.anthropic.model'))->toBeString();
});
```

- [ ] **Step 2: Run — expect FAIL** (`php artisan test --filter=ScrapingConfigTest`).

- [ ] **Step 3: Create `config/scraping.php`:**

```php
<?php

return [
    'candidate_paths' => ['/termine', '/seminare', '/events', '/kurse', '/veranstaltungen', '/workshops'],
    'timeout' => (int) env('SCRAPING_TIMEOUT', 20),
    'max_detail_pages' => (int) env('SCRAPING_MAX_DETAIL_PAGES', 25),
    // Static EUR conversion rates (1 unit of currency = X EUR). Tune as needed.
    'fx' => [
        'EUR' => 1.0,
        'CHF' => 1.05,
        'USD' => 0.92,
        'GBP' => 1.17,
    ],
];
```

- [ ] **Step 4: Add to `config/services.php`** (before the closing `];`, alongside the existing entries):

```php
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
    ],
```

- [ ] **Step 5: Append to `.env.example`:**

```env
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-sonnet-4-5
SCRAPING_TIMEOUT=20
```

- [ ] **Step 6: Run `php artisan test --filter=ScrapingConfigTest` (PASS), full suite, `vendor/bin/pint --test`.**

- [ ] **Step 7: Commit**

```bash
git add config/scraping.php config/services.php .env.example tests/Feature/Scraping/ScrapingConfigTest.php
git commit -m "feat(scraping): config for candidate paths, FX rates, and Anthropic"
```

---

### Task 2: organizers.events_url + last_scraped_at columns

**Files:**
- Create: `database/migrations/2026_06_03_000013_add_scraping_fields_to_organizers_table.php`
- Modify: `app/Models/Organizer.php`
- Test: `tests/Feature/Scraping/OrganizerScrapingFieldsTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/Scraping/OrganizerScrapingFieldsTest.php`:

```php
<?php

use App\Models\Organizer;

it('stores events_url and last_scraped_at on an organizer', function () {
    $o = Organizer::factory()->create([
        'events_url' => 'https://example.com/termine',
        'last_scraped_at' => now(),
    ]);

    expect($o->refresh()->events_url)->toBe('https://example.com/termine')
        ->and($o->last_scraped_at)->not->toBeNull();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Migration** `2026_06_03_000013_add_scraping_fields_to_organizers_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('events_url')->nullable()->after('website');
            $table->timestamp('last_scraped_at')->nullable()->after('vat_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn(['events_url', 'last_scraped_at']);
        });
    }
};
```

- [ ] **Step 4: Update `app/Models/Organizer.php`** — add `'events_url'` and `'last_scraped_at'` to `$fillable`, and add `'last_scraped_at' => 'datetime'` to the `casts()` array.

- [ ] **Step 5: Run filter (PASS), full suite, pint.**

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_03_000013_add_scraping_fields_to_organizers_table.php app/Models/Organizer.php tests/Feature/Scraping/OrganizerScrapingFieldsTest.php
git commit -m "feat(scraping): organizers events_url + last_scraped_at"
```

---

### Task 3: ScrapedEvent DTO

**Files:**
- Create: `app/Scraping/ScrapedEvent.php`
- Test: `tests/Unit/Scraping/ScrapedEventTest.php`

- [ ] **Step 1: Failing test** — `tests/Unit/Scraping/ScrapedEventTest.php`:

```php
<?php

use App\Scraping\ScrapedEvent;

it('builds from an array with defaults', function () {
    $e = ScrapedEvent::fromArray([
        'title' => 'Tantra Weekend',
        'start_date' => '2026-09-01 10:00',
        'source_url' => 'https://x.de/e/1',
        'prices' => [['amount' => 199.0, 'currency' => 'EUR']],
    ]);

    expect($e->title)->toBe('Tantra Weekend')
        ->and($e->sourceUrl)->toBe('https://x.de/e/1')
        ->and($e->prices)->toHaveCount(1)
        ->and($e->endDate)->toBeNull()
        ->and($e->bookingUrl)->toBe('https://x.de/e/1');
});

it('ignores entries without a title or start date', function () {
    expect(ScrapedEvent::fromArray(['source_url' => 'x']))->toBeNull();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** `app/Scraping/ScrapedEvent.php`:

```php
<?php

namespace App\Scraping;

class ScrapedEvent
{
    /**
     * @param  array<int, array{amount: float, currency: string}>  $prices
     * @param  array<int, string>  $languages
     */
    public function __construct(
        public readonly string $title,
        public readonly string $startDate,
        public readonly ?string $endDate,
        public readonly string $sourceUrl,
        public readonly string $bookingUrl,
        public readonly ?string $city = null,
        public readonly ?string $description = null,
        public readonly ?string $imageUrl = null,
        public readonly array $prices = [],
        public readonly array $languages = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $title = trim((string) ($data['title'] ?? ''));
        $start = trim((string) ($data['start_date'] ?? ''));
        $source = trim((string) ($data['source_url'] ?? ''));

        if ($title === '' || $start === '') {
            return null;
        }

        return new self(
            title: $title,
            startDate: $start,
            endDate: ($data['end_date'] ?? null) ? (string) $data['end_date'] : null,
            sourceUrl: $source,
            bookingUrl: trim((string) ($data['booking_url'] ?? $source)),
            city: isset($data['city']) ? (string) $data['city'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            imageUrl: isset($data['image_url']) ? (string) $data['image_url'] : null,
            prices: array_values($data['prices'] ?? []),
            languages: array_values($data['languages'] ?? []),
        );
    }
}
```

- [ ] **Step 4: Run filter (PASS), full suite, pint.**

- [ ] **Step 5: Commit**

```bash
git add app/Scraping/ScrapedEvent.php tests/Unit/Scraping/ScrapedEventTest.php
git commit -m "feat(scraping): ScrapedEvent DTO"
```

(Note: `tests/Unit` is not bound to RefreshDatabase — this DTO test touches no DB, so it belongs in Unit.)

---

### Task 4: CurrencyNormalizer (→ EUR)

**Files:**
- Create: `app/Scraping/CurrencyNormalizer.php`
- Test: `tests/Unit/Scraping/CurrencyNormalizerTest.php`

- [ ] **Step 1: Failing test** — `tests/Unit/Scraping/CurrencyNormalizerTest.php`:

```php
<?php

use App\Scraping\CurrencyNormalizer;

beforeEach(fn () => $this->n = new CurrencyNormalizer(['EUR' => 1.0, 'CHF' => 1.05, 'USD' => 0.92]));

it('keeps EUR amounts', function () {
    expect($this->n->toEur(100.0, 'EUR'))->toBe(100.0);
});

it('treats unknown/missing currency as EUR', function () {
    expect($this->n->toEur(50.0, null))->toBe(50.0)
        ->and($this->n->toEur(50.0, 'XYZ'))->toBe(50.0);
});

it('converts CHF and USD to EUR', function () {
    expect($this->n->toEur(100.0, 'CHF'))->toBe(105.0)
        ->and($this->n->toEur(100.0, 'USD'))->toBe(92.0);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** `app/Scraping/CurrencyNormalizer.php`:

```php
<?php

namespace App\Scraping;

class CurrencyNormalizer
{
    /** @param array<string, float> $rates */
    public function __construct(private readonly array $rates) {}

    public static function fromConfig(): self
    {
        return new self(config('scraping.fx', ['EUR' => 1.0]));
    }

    public function toEur(float $amount, ?string $currency): float
    {
        $code = strtoupper((string) ($currency ?: 'EUR'));
        $rate = $this->rates[$code] ?? 1.0;

        return round($amount * $rate, 2);
    }
}
```

- [ ] **Step 4: Run filter (PASS), full suite, pint.**

- [ ] **Step 5: Commit**

```bash
git add app/Scraping/CurrencyNormalizer.php tests/Unit/Scraping/CurrencyNormalizerTest.php
git commit -m "feat(scraping): CurrencyNormalizer to EUR"
```

---

### Task 5: EventExtractor interface + StructuredDataExtractor (JSON-LD)

**Files:**
- Create: `app/Scraping/Extractors/EventExtractor.php`, `app/Scraping/Extractors/StructuredDataExtractor.php`
- Test: `tests/Unit/Scraping/StructuredDataExtractorTest.php`

- [ ] **Step 1: Failing test** — `tests/Unit/Scraping/StructuredDataExtractorTest.php`:

```php
<?php

use App\Scraping\Extractors\StructuredDataExtractor;

it('extracts schema.org Event JSON-LD from html', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"Event","name":"Tantra Weekend",
     "startDate":"2026-09-01T10:00","endDate":"2026-09-03T17:00",
     "url":"https://x.de/e/1","location":{"@type":"Place","address":{"addressLocality":"Hamburg"}},
     "offers":{"@type":"Offer","price":"199","priceCurrency":"EUR"}}
    </script></head><body></body></html>
    HTML;

    $events = (new StructuredDataExtractor())->extract($html, 'https://x.de/termine');

    expect($events)->toHaveCount(1);
    $e = $events[0];
    expect($e->title)->toBe('Tantra Weekend')
        ->and($e->city)->toBe('Hamburg')
        ->and($e->sourceUrl)->toBe('https://x.de/e/1')
        ->and($e->prices[0]['amount'])->toBe(199.0)
        ->and($e->prices[0]['currency'])->toBe('EUR');
});

it('returns empty array when no JSON-LD present', function () {
    expect((new StructuredDataExtractor())->extract('<html></html>', 'https://x.de'))->toBe([]);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Interface** `app/Scraping/Extractors/EventExtractor.php`:

```php
<?php

namespace App\Scraping\Extractors;

use App\Scraping\ScrapedEvent;

interface EventExtractor
{
    /**
     * @return array<int, ScrapedEvent>
     */
    public function extract(string $html, string $pageUrl): array;
}
```

- [ ] **Step 4: Implement** `app/Scraping/Extractors/StructuredDataExtractor.php` — find all `<script type="application/ld+json">` blocks, JSON-decode (tolerant), walk for nodes with `@type` containing `Event`, map to `ScrapedEvent`:

```php
<?php

namespace App\Scraping\Extractors;

use App\Scraping\ScrapedEvent;

class StructuredDataExtractor implements EventExtractor
{
    public function extract(string $html, string $pageUrl): array
    {
        $events = [];

        if (! preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $m)) {
            return [];
        }

        foreach ($m[1] as $block) {
            $data = json_decode(trim($block), true);
            if (! is_array($data)) {
                continue;
            }
            foreach ($this->eventNodes($data) as $node) {
                $event = $this->toEvent($node, $pageUrl);
                if ($event !== null) {
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function eventNodes(array $data): array
    {
        // Unwrap @graph and arrays of nodes.
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $data = $data['@graph'];
        }
        $nodes = array_is_list($data) ? $data : [$data];

        return array_values(array_filter($nodes, function ($n) {
            if (! is_array($n) || ! isset($n['@type'])) {
                return false;
            }
            $type = is_array($n['@type']) ? implode(',', $n['@type']) : (string) $n['@type'];

            return str_contains($type, 'Event');
        }));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function toEvent(array $node, string $pageUrl): ?ScrapedEvent
    {
        $offer = $node['offers'] ?? null;
        if (isset($offer[0])) {
            $offer = $offer[0];
        }
        $prices = [];
        if (is_array($offer) && isset($offer['price'])) {
            $prices[] = [
                'amount' => (float) $offer['price'],
                'currency' => strtoupper((string) ($offer['priceCurrency'] ?? 'EUR')),
            ];
        }

        $city = $node['location']['address']['addressLocality'] ?? null;

        return ScrapedEvent::fromArray([
            'title' => $node['name'] ?? null,
            'start_date' => $node['startDate'] ?? null,
            'end_date' => $node['endDate'] ?? null,
            'source_url' => $node['url'] ?? $pageUrl,
            'city' => $city,
            'description' => $node['description'] ?? null,
            'image_url' => is_array($node['image'] ?? null) ? ($node['image'][0] ?? null) : ($node['image'] ?? null),
            'prices' => $prices,
        ]);
    }
}
```

- [ ] **Step 5: Run filter (PASS), full suite, pint.**

- [ ] **Step 6: Commit**

```bash
git add app/Scraping/Extractors/EventExtractor.php app/Scraping/Extractors/StructuredDataExtractor.php tests/Unit/Scraping/StructuredDataExtractorTest.php
git commit -m "feat(scraping): EventExtractor interface + JSON-LD StructuredDataExtractor"
```

---

### Task 6: LlmClient interface + LlmEventExtractor (+ Anthropic client)

**Files:**
- Create: `app/Scraping/Llm/LlmClient.php`, `app/Scraping/Llm/AnthropicLlmClient.php`, `app/Scraping/Extractors/LlmEventExtractor.php`
- Test: `tests/Unit/Scraping/LlmEventExtractorTest.php`

- [ ] **Step 1: Failing test** — `tests/Unit/Scraping/LlmEventExtractorTest.php`:

```php
<?php

use App\Scraping\Extractors\LlmEventExtractor;
use App\Scraping\Llm\LlmClient;

it('maps llm json output to scraped events', function () {
    $fake = new class implements LlmClient
    {
        public function extractEvents(string $content, string $pageUrl): array
        {
            return [[
                'title' => 'Conscious Dance',
                'start_date' => '2026-10-10 19:00',
                'source_url' => 'https://x.de/e/9',
                'city' => 'Berlin',
                'prices' => [['amount' => 25.0, 'currency' => 'EUR']],
            ]];
        }
    };

    $events = (new LlmEventExtractor($fake))->extract('<html>...</html>', 'https://x.de/termine');

    expect($events)->toHaveCount(1)
        ->and($events[0]->title)->toBe('Conscious Dance')
        ->and($events[0]->city)->toBe('Berlin');
});

it('returns empty when the llm client has no api key configured', function () {
    config()->set('services.anthropic.api_key', null);
    $extractor = new LlmEventExtractor(new App\Scraping\Llm\AnthropicLlmClient());

    expect($extractor->extract('<html></html>', 'https://x.de'))->toBe([]);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Interface** `app/Scraping/Llm/LlmClient.php`:

```php
<?php

namespace App\Scraping\Llm;

interface LlmClient
{
    /**
     * @return array<int, array<string, mixed>>  Raw event arrays (title, start_date, source_url, ...).
     */
    public function extractEvents(string $content, string $pageUrl): array;
}
```

- [ ] **Step 4: Anthropic client** `app/Scraping/Llm/AnthropicLlmClient.php` — calls the Anthropic Messages API via the Laravel HTTP client; returns `[]` when no api key, on any error, or if the response is unparseable. Trims the page content to a sane length.

```php
<?php

namespace App\Scraping\Llm;

use Illuminate\Support\Facades\Http;
use Throwable;

class AnthropicLlmClient implements LlmClient
{
    public function extractEvents(string $content, string $pageUrl): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            return [];
        }

        $prompt = "Extract all events from this page as a JSON array. Each item: "
            ."{title, start_date (Y-m-d H:i), end_date|null, source_url (absolute), city|null, "
            ."description|null, image_url|null, prices:[{amount:number, currency:ISO}]}. "
            ."Page URL: {$pageUrl}. Return ONLY the JSON array, no prose.\n\n"
            .mb_substr(strip_tags($content), 0, 60000);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 4096,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (! $response->successful()) {
                return [];
            }

            $text = $response->json('content.0.text', '');
            $text = trim(preg_replace('/^```(?:json)?|```$/m', '', (string) $text));
            $decoded = json_decode($text, true);

            return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
        } catch (Throwable) {
            return [];
        }
    }
}
```

- [ ] **Step 5: Extractor** `app/Scraping/Extractors/LlmEventExtractor.php`:

```php
<?php

namespace App\Scraping\Extractors;

use App\Scraping\Llm\LlmClient;
use App\Scraping\ScrapedEvent;

class LlmEventExtractor implements EventExtractor
{
    public function __construct(private readonly LlmClient $client) {}

    public function extract(string $html, string $pageUrl): array
    {
        $events = [];
        foreach ($this->client->extractEvents($html, $pageUrl) as $row) {
            $event = ScrapedEvent::fromArray($row);
            if ($event !== null) {
                $events[] = $event;
            }
        }

        return $events;
    }
}
```

- [ ] **Step 6: Run filter (PASS), full suite, pint.**

- [ ] **Step 7: Commit**

```bash
git add app/Scraping/Llm/LlmClient.php app/Scraping/Llm/AnthropicLlmClient.php app/Scraping/Extractors/LlmEventExtractor.php tests/Unit/Scraping/LlmEventExtractorTest.php
git commit -m "feat(scraping): LLM extractor with Anthropic client behind an interface"
```

---

### Task 7: PageFetcher + EventsUrlResolver

**Files:**
- Create: `app/Scraping/PageFetcher.php`, `app/Scraping/HttpPageFetcher.php`, `app/Scraping/EventsUrlResolver.php`
- Test: `tests/Unit/Scraping/EventsUrlResolverTest.php`

- [ ] **Step 1: Failing test** — `tests/Unit/Scraping/EventsUrlResolverTest.php`:

```php
<?php

use App\Models\Organizer;
use App\Scraping\EventsUrlResolver;

it('prefers events_url then website candidate paths', function () {
    $o = Organizer::factory()->make(['website' => 'https://x.de', 'events_url' => 'https://x.de/termine']);
    $candidates = (new EventsUrlResolver())->candidates($o);

    expect($candidates[0])->toBe('https://x.de/termine')
        ->and($candidates)->toContain('https://x.de/seminare');
});

it('falls back to website candidate paths when events_url is null', function () {
    $o = Organizer::factory()->make(['website' => 'https://x.de', 'events_url' => null]);
    $candidates = (new EventsUrlResolver())->candidates($o);

    expect($candidates[0])->toBe('https://x.de/termine')
        ->and($candidates)->toContain('https://x.de');
});
```

(`Organizer::factory()->make()` builds without DB — keep this test in Unit only if it does not hit the DB; `make()` does not. If the factory touches the DB via relations, move to `tests/Feature`. Use `->make()` to avoid persistence.)

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: PageFetcher interface** `app/Scraping/PageFetcher.php`:

```php
<?php

namespace App\Scraping;

interface PageFetcher
{
    public function get(string $url): ?string;
}
```

- [ ] **Step 4: HttpPageFetcher** `app/Scraping/HttpPageFetcher.php`:

```php
<?php

namespace App\Scraping;

use Illuminate\Support\Facades\Http;
use Throwable;

class HttpPageFetcher implements PageFetcher
{
    public function get(string $url): ?string
    {
        try {
            $response = Http::timeout((int) config('scraping.timeout', 20))
                ->withHeaders(['User-Agent' => 'ErotischeEventsBot/1.0 (+https://erotische-events.com)'])
                ->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 5: EventsUrlResolver** `app/Scraping/EventsUrlResolver.php`:

```php
<?php

namespace App\Scraping;

use App\Models\Organizer;

class EventsUrlResolver
{
    /**
     * @return array<int, string>  Ordered candidate listing URLs to try.
     */
    public function candidates(Organizer $organizer): array
    {
        $urls = [];

        if (! empty($organizer->events_url)) {
            $urls[] = $organizer->events_url;
        }

        $base = rtrim((string) $organizer->website, '/');
        if ($base !== '') {
            foreach (config('scraping.candidate_paths', []) as $path) {
                $urls[] = $base.$path;
            }
            $urls[] = $base;
        }

        return array_values(array_unique($urls));
    }
}
```

- [ ] **Step 6: Run filter (PASS), full suite, pint.**

- [ ] **Step 7: Commit**

```bash
git add app/Scraping/PageFetcher.php app/Scraping/HttpPageFetcher.php app/Scraping/EventsUrlResolver.php tests/Unit/Scraping/EventsUrlResolverTest.php
git commit -m "feat(scraping): PageFetcher + EventsUrlResolver"
```

---

### Task 8: EventImportService (upsert, dedup, EUR, override rule)

**Files:**
- Create: `app/Scraping/EventImportService.php`
- Test: `tests/Feature/Scraping/EventImportServiceTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/Scraping/EventImportServiceTest.php`:

```php
<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Scraping\EventImportService;
use App\Scraping\ScrapedEvent;

function importer(): EventImportService
{
    return app(EventImportService::class);
}

function scraped(array $overrides = []): ScrapedEvent
{
    return ScrapedEvent::fromArray(array_merge([
        'title' => 'Tantra Weekend',
        'start_date' => '2026-09-01 10:00',
        'source_url' => 'https://x.de/e/1',
        'prices' => [['amount' => 100.0, 'currency' => 'CHF']],
    ], $overrides));
}

it('imports a scraped event as published with EUR price and organizer context', function () {
    $org = Organizer::factory()->approved()->create(['category' => 'tantra']);

    importer()->import($org, [scraped()]);

    $event = Event::where('source_url', 'https://x.de/e/1')->firstOrFail();
    expect($event->status)->toBe(EventStatus::Published)
        ->and($event->organizer_id)->toBe($org->id)
        ->and($event->currency)->toBe('EUR')
        ->and((float) $event->prices->first()->amount)->toBe(105.0) // 100 CHF * 1.05
        ->and($event->prices->first()->currency)->toBe('EUR');
});

it('is idempotent on re-import (updates, not duplicates)', function () {
    $org = Organizer::factory()->approved()->create();
    importer()->import($org, [scraped(['title' => 'V1'])]);
    importer()->import($org, [scraped(['title' => 'V2'])]);

    expect(Event::where('source_url', 'https://x.de/e/1')->count())->toBe(1)
        ->and(Event::where('source_url', 'https://x.de/e/1')->first()->title)->toBe('V2');
});

it('never touches manually created events (source_url null)', function () {
    $org = Organizer::factory()->approved()->create();
    $manual = Event::factory()->create(['organizer_id' => $org->id, 'source_url' => null, 'title' => 'Manual']);

    importer()->import($org, [scraped()]);

    expect($manual->fresh()->title)->toBe('Manual');
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** `app/Scraping/EventImportService.php`:

```php
<?php

namespace App\Scraping;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Support\Str;

class EventImportService
{
    public function __construct(private readonly CurrencyNormalizer $currency) {}

    /**
     * @param  array<int, ScrapedEvent>  $events
     * @return array{created: int, updated: int}
     */
    public function import(Organizer $organizer, array $events): array
    {
        $created = 0;
        $updated = 0;
        $venueId = $organizer->venues()->value('id');

        foreach ($events as $scraped) {
            $existing = Event::query()
                ->where('organizer_id', $organizer->id)
                ->whereNotNull('source_url')
                ->where('source_url', $scraped->sourceUrl)
                ->first();

            $isNew = $existing === null;
            $event = $existing ?? new Event(['organizer_id' => $organizer->id]);

            $event->fill([
                'title' => $scraped->title,
                'slug' => $event->slug ?: Str::slug($scraped->title).'-'.Str::lower(Str::random(6)),
                'short_description' => $scraped->description ? Str::limit($scraped->description, 250) : null,
                'long_description' => $scraped->description,
                'start_date' => $scraped->startDate,
                'end_date' => $scraped->endDate,
                'status' => EventStatus::Published,
                'currency' => 'EUR',
                'booking_url' => $scraped->bookingUrl,
                'source_url' => $scraped->sourceUrl,
                'venue_id' => $event->venue_id ?: $venueId,
                'languages' => $scraped->languages ?: ['de'],
            ]);
            $event->save();

            // Prices: replace the scraper-managed prices, normalized to EUR.
            $event->prices()->delete();
            foreach ($scraped->prices as $price) {
                $event->prices()->create([
                    'type' => 'regular',
                    'amount' => $this->currency->toEur((float) ($price['amount'] ?? 0), $price['currency'] ?? null),
                    'currency' => 'EUR',
                ]);
            }

            // Category from the organizer's sheet category (if it maps to a known category slug).
            if ($organizer->category) {
                $categoryId = \App\Models\Category::where('slug', $organizer->category)->value('id');
                if ($categoryId) {
                    $event->categories()->syncWithoutDetaching([$categoryId]);
                }
            }

            $isNew ? $created++ : $updated++;
        }

        return ['created' => $created, 'updated' => $updated];
    }
}
```

Bind `CurrencyNormalizer` in `AppServiceProvider::register()`:

```php
$this->app->bind(\App\Scraping\CurrencyNormalizer::class, fn () => \App\Scraping\CurrencyNormalizer::fromConfig());
```

- [ ] **Step 4: Run filter (PASS), full suite, pint.**

- [ ] **Step 5: Commit**

```bash
git add app/Scraping/EventImportService.php app/Providers/AppServiceProvider.php tests/Feature/Scraping/EventImportServiceTest.php
git commit -m "feat(scraping): EventImportService (published, EUR, dedup, override-safe)"
```

---

### Task 9: EventScraperService (orchestrator)

**Files:**
- Create: `app/Scraping/EventScraperService.php`
- Modify: `app/Providers/AppServiceProvider.php` (bind the extractor chain)
- Test: `tests/Feature/Scraping/EventScraperServiceTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/Scraping/EventScraperServiceTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Organizer;
use App\Scraping\EventScraperService;
use App\Scraping\Extractors\EventExtractor;
use App\Scraping\PageFetcher;
use App\Scraping\ScrapedEvent;

it('fetches, extracts and imports events for an organizer', function () {
    $org = Organizer::factory()->approved()->create(['website' => 'https://x.de', 'events_url' => 'https://x.de/termine']);

    $fetcher = new class implements PageFetcher
    {
        public function get(string $url): ?string
        {
            return $url === 'https://x.de/termine' ? '<html>list</html>' : null;
        }
    };

    $extractor = new class implements EventExtractor
    {
        public function extract(string $html, string $pageUrl): array
        {
            return [ScrapedEvent::fromArray([
                'title' => 'Scraped Event',
                'start_date' => '2026-09-01 10:00',
                'source_url' => 'https://x.de/e/1',
            ])];
        }
    };

    $service = new EventScraperService($fetcher, [$extractor], app(App\Scraping\EventImportService::class), new App\Scraping\EventsUrlResolver());
    $result = $service->scrape($org);

    expect($result['created'])->toBe(1)
        ->and(Event::where('source_url', 'https://x.de/e/1')->exists())->toBeTrue()
        ->and($org->fresh()->last_scraped_at)->not->toBeNull();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** `app/Scraping/EventScraperService.php`:

```php
<?php

namespace App\Scraping;

use App\Models\Organizer;
use App\Scraping\Extractors\EventExtractor;

class EventScraperService
{
    /**
     * @param  array<int, EventExtractor>  $extractors  ordered: structured-data first, LLM fallback last
     */
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly array $extractors,
        private readonly EventImportService $importer,
        private readonly EventsUrlResolver $resolver,
    ) {}

    /**
     * @return array{created: int, updated: int, url: ?string}
     */
    public function scrape(Organizer $organizer): array
    {
        $result = ['created' => 0, 'updated' => 0, 'url' => null];

        foreach ($this->resolver->candidates($organizer) as $url) {
            $html = $this->fetcher->get($url);
            if ($html === null) {
                continue;
            }

            foreach ($this->extractors as $extractor) {
                $events = $extractor->extract($html, $url);
                if ($events !== []) {
                    $imported = $this->importer->import($organizer, $events);
                    $result = [...$imported, 'url' => $url];
                    break 2;
                }
            }
        }

        $organizer->forceFill(['last_scraped_at' => now()])->save();

        return $result;
    }
}
```

Bind in `AppServiceProvider::register()` so the real chain is injected for the command:

```php
$this->app->bind(\App\Scraping\EventScraperService::class, function ($app) {
    return new \App\Scraping\EventScraperService(
        $app->make(\App\Scraping\HttpPageFetcher::class),
        [
            $app->make(\App\Scraping\Extractors\StructuredDataExtractor::class),
            new \App\Scraping\Extractors\LlmEventExtractor($app->make(\App\Scraping\Llm\AnthropicLlmClient::class)),
        ],
        $app->make(\App\Scraping\EventImportService::class),
        $app->make(\App\Scraping\EventsUrlResolver::class),
    );
});
```

- [ ] **Step 4: Run filter (PASS), full suite, pint.**

- [ ] **Step 5: Commit**

```bash
git add app/Scraping/EventScraperService.php app/Providers/AppServiceProvider.php tests/Feature/Scraping/EventScraperServiceTest.php
git commit -m "feat(scraping): EventScraperService orchestrator with extractor chain"
```

---

### Task 10: events:scrape command

**Files:**
- Create: `app/Console/Commands/ScrapeEvents.php`
- Test: `tests/Feature/Scraping/ScrapeEventsCommandTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/Scraping/ScrapeEventsCommandTest.php`:

```php
<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Scraping\EventScraperService;

it('scrapes only the given organizer and skips rejected ones', function () {
    $target = Organizer::factory()->approved()->create(['slug' => 'target-org']);
    Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Rejected]);

    // Swap the service for a mock that creates a marker event for whatever organizer it is given.
    $mock = Mockery::mock(EventScraperService::class);
    $mock->shouldReceive('scrape')->andReturnUsing(function (Organizer $o) {
        Event::factory()->create(['organizer_id' => $o->id, 'source_url' => 'https://t/'.$o->id]);

        return ['created' => 1, 'updated' => 0, 'url' => 'https://t'];
    });
    $this->app->instance(EventScraperService::class, $mock);

    $this->artisan('events:scrape', ['--organizer' => 'target-org'])->assertSuccessful();

    expect(Event::where('organizer_id', $target->id)->count())->toBe(1)
        ->and(Event::count())->toBe(1);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** `app/Console/Commands/ScrapeEvents.php`:

```php
<?php

namespace App\Console\Commands;

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Scraping\EventScraperService;
use Illuminate\Console\Command;

class ScrapeEvents extends Command
{
    protected $signature = 'events:scrape {--organizer= : Slug or id of a single organizer to scrape}';

    protected $description = 'Scrape and import events for non-rejected organizers (or one).';

    public function handle(EventScraperService $scraper): int
    {
        $query = Organizer::query()->where('verification_status', '!=', OrganizerVerificationStatus::Rejected->value);

        if ($option = $this->option('organizer')) {
            $query->where(fn ($q) => $q->where('slug', $option)->orWhere('id', $option));
        }

        $organizers = $query->get();
        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($organizers as $organizer) {
            try {
                $r = $scraper->scrape($organizer);
                $totalCreated += $r['created'];
                $totalUpdated += $r['updated'];
                $this->line("{$organizer->slug}: +{$r['created']} new, {$r['updated']} updated");
            } catch (\Throwable $e) {
                $this->warn("{$organizer->slug}: failed — {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Done. {$organizers->count()} organizer(s), {$totalCreated} created, {$totalUpdated} updated.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run filter (PASS), full suite, pint.**

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ScrapeEvents.php tests/Feature/Scraping/ScrapeEventsCommandTest.php
git commit -m "feat(scraping): events:scrape command (all or single organizer)"
```

---

### Task 11: Daily schedule

**Files:**
- Modify: `routes/console.php`
- Test: `tests/Feature/Scraping/ScrapeScheduleTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/Scraping/ScrapeScheduleTest.php`:

```php
<?php

use Illuminate\Console\Scheduling\Schedule;

it('schedules the events:scrape command daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'events:scrape'));

    expect($events)->not->toBeEmpty();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Add to `routes/console.php`:**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('events:scrape')->daily();
```

- [ ] **Step 4: Run filter (PASS), full suite, pint.**

- [ ] **Step 5: Commit**

```bash
git add routes/console.php tests/Feature/Scraping/ScrapeScheduleTest.php
git commit -m "feat(scraping): schedule events:scrape daily"
```

---

### Task 12: Backfill events_url from the source sheet

**Files:**
- Modify: `database/seeders/data/organizers.json` (add `events_url`), `database/seeders/OrganizerSeeder.php`
- Test: `tests/Feature/Scraping/EventsUrlSeedTest.php`

- [ ] **Step 1:** Re-read the source Google Sheet (Drive MCP `read_file_content` on the sheet id `1RarKgKeRxE4KayUzEseHT9ZjJRBUkRHH6vgXcI8I3Eg`), and for each row map the host (strip `www.`) to the row's ORIGINAL URL (the listing URL, often a deep path). Keeping the FIRST occurrence per host (same dedup as `organizers.json`), add an `events_url` field to each object in `database/seeders/data/organizers.json` equal to that original URL. (This is a data-regeneration step; preserve all existing fields.)

- [ ] **Step 2: Failing test** — `tests/Feature/Scraping/EventsUrlSeedTest.php`:

```php
<?php

use App\Models\Organizer;
use Database\Seeders\OrganizerSeeder;

it('seeds events_url from the source listing url', function () {
    $this->seed(OrganizerSeeder::class);

    $o = Organizer::where('slug', 'secret-of-tantra-de')->firstOrFail();
    expect($o->events_url)->not->toBeNull()
        ->and($o->events_url)->toContain('secret-of-tantra.de');
});
```

- [ ] **Step 3:** In `database/seeders/OrganizerSeeder.php` base `updateOrCreate` array, add `'events_url' => $row['events_url'] ?? null,`.

- [ ] **Step 4: Run filter (PASS), full suite, pint.** Then backfill the live DB: `./vendor/bin/sail artisan db:seed --class=OrganizerSeeder`.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/data/organizers.json database/seeders/OrganizerSeeder.php tests/Feature/Scraping/EventsUrlSeedTest.php
git commit -m "feat(scraping): seed organizers.events_url from source listing URLs"
```

---

### Task 13: Pilot run + rollout (live, requires ANTHROPIC_API_KEY)

Not a code task — operational, run after the engine is green.

- [ ] **Step 1:** Confirm `ANTHROPIC_API_KEY` is set in `root/.env` (else only the structured-data path runs). 
- [ ] **Step 2: Pilot** — choose an organizer with a real structured events list and run: `./vendor/bin/sail artisan events:scrape --organizer=<slug>`; verify created events are published, EUR-priced, attached to the organizer + primary venue, visible at `/events` and `/organizers/<slug>`.
- [ ] **Step 3: Rollout** — `./vendor/bin/sail artisan events:scrape` over all non-rejected organizers; record created/updated totals and any failures.

(Run these via `docker compose exec` if the sandbox blocks the Docker socket.)

---

## Self-Review (run after all tasks)

- [ ] **Spec coverage:** generic engine (T9) ✓; structured-data extractor (T5) ✓; Anthropic LLM fallback behind interface (T6) ✓; EUR currency normalization (T4 + applied in T8) ✓; status=published + dedup + override rule (T8) ✓; events_url column + sheet backfill + path-probe resolver (T2, T7, T12) ✓; CLI all/single (T10) ✓; daily cron (T11) ✓; config + key (T1) ✓; pilot + rollout (T13) ✓.
- [ ] **Type/name consistency:** `EventExtractor::extract(string,string): ScrapedEvent[]`, `LlmClient::extractEvents(string,string): array`, `PageFetcher::get(string): ?string`, `EventsUrlResolver::candidates(Organizer): string[]`, `CurrencyNormalizer::toEur(float,?string): float`, `EventImportService::import(Organizer, ScrapedEvent[]): {created,updated}`, `EventScraperService::scrape(Organizer): {created,updated,url}` — used identically across tasks.
- [ ] **No placeholders;** every code step is complete.
- [ ] `php artisan test` green; `vendor/bin/pint --test` clean throughout.
