# Design: Event Scraper (generic engine, A1)

Date: 2026-06-03
Status: Approved (brainstorming) — ready for implementation planning
Parent: `docs/01-prd.md` (§9 Scraper Platform), `docs/03-database-schema.md`

## 1. Goal & Decision

Import events for organizers by scraping their websites, on a recurring cron schedule,
with the ability to update a single organizer on demand. Applies to all **non-rejected**
organizers (currently 109).

**Decision:** ONE generic engine with a prioritized extractor chain — **not** a bespoke
scraper per organizer. The generic engine fully supports both requirements: cron
(scheduler invokes it) and single-organizer updates (`--organizer=` argument). Per-organizer
code is avoided; only optional per-site config/overrides where needed.

## 2. Architecture (`app/Scraping/`)

Separate subsystem (PRD: the scraper is a separate system).

- **`EventScraperService`** — orchestrates per organizer: resolve events URL → fetch →
  run the extractor chain (first chain member that yields events wins) → normalize →
  hand `ScrapedEvent[]` to the importer.
- **Extractor chain** (interface `EventExtractor`, ordered):
  1. **`StructuredDataExtractor`** — parse JSON-LD `schema.org/Event` from the events page
     and linked detail pages; also detect iCal/RSS and known calendar-plugin REST endpoints
     (e.g. The Events Calendar `/wp-json/tribe/events/v1/events`).
  2. **`LlmEventExtractor`** (fallback) — send the events page text/HTML to Claude
     (Anthropic) with a structured-output instruction → events JSON. Sits behind an
     **`LlmClient`** interface (mockable in tests, provider-swappable). Skipped gracefully
     when no API key is configured.
- **`ScrapedEvent` DTO** — normalized fields: `title`, `start_date`, `end_date?`,
  `city?`/`location?`, `prices[]` (amount + source currency), `source_url`, `description?`,
  `image_url?`, `languages?`, `booking_url`.
- **HTTP fetching** — Laravel HTTP client with timeout; `EventsUrlResolver` chooses the URL
  (see below). One events-listing fetch + bounded detail-page fetches per organizer.

### Events URL source
Add column **`events_url`** to `organizers`, backfilled from the **original Google-Sheet URL**
(often the seminars/termine page, e.g. `secret-of-tantra.de/termine-seminare/`, lost during
domain dedup). `EventsUrlResolver`: use `events_url` if set; else probe candidate paths on the
website (`/termine`, `/seminare`, `/events`, `/kurse`, `/veranstaltungen`) and pick the one
yielding the most extractable events.

## 3. Import, dedup, currency, override rule

- **`EventImportService`** creates/updates `events`: `organizer_id`, `status = published`,
  `source_url`, `booking_url` (= detail/source URL), default `venue_id` = organizer's primary
  venue, default category = organizer's `category`; writes `event_prices`.
- **Currency normalization to EUR:** a **`CurrencyNormalizer`** converts every scraped price to
  EUR using a configurable rate table in `config/scraping.php` (e.g. CHF/USD → EUR); prices
  without an explicit currency are treated as EUR. After import, `event_prices.currency` is
  always `EUR` and `events.currency` is `EUR`.
- **Dedup / idempotency:** key = `source_url` when present, else
  (`organizer_id` + `title` + `start_date`). Re-scrape **updates** the matching event instead
  of duplicating.
- **Organizer-override rule (PRD):** the scraper only ever touches events with a non-null
  `source_url` (scraper-owned). Manually created events (`source_url = null`) are never
  modified or deleted.

## 4. CLI, cron, robustness

- **Command** `php artisan events:scrape {--organizer=}` — no option: all non-rejected
  organizers; `--organizer=slug|id`: only that one. Reports per-organizer counts (found /
  imported / updated / skipped) and a summary.
- **Cron:** `routes/console.php` → `Schedule::command('events:scrape')->daily();` Run in prod
  via system cron `php artisan schedule:run` (or `sail artisan schedule:work`).
- **Robustness:** per-organizer try/catch (one failure never aborts the batch); HTTP timeouts;
  structured logging; `last_scraped_at` timestamp on the organizer; unreachable site / no
  events → skipped, not fatal. The LLM fallback is rate/again-bounded and skipped without a key.

## 5. Config & secrets

- `config/services.php` → `anthropic` = `['api_key' => env('ANTHROPIC_API_KEY'), 'model' => env('ANTHROPIC_MODEL', '<current Claude model, pinned at implementation>')]`.
- `config/scraping.php` → candidate event paths, FX rate table, fetch timeout, max detail pages.
- **Required to run the live AI pass:** `ANTHROPIC_API_KEY` in `root/.env`. Without it only the
  structured-data path runs.

## 6. Testing (no network)

- `StructuredDataExtractor`: JSON-LD fixture HTML → asserts parsed `ScrapedEvent[]`.
- `LlmEventExtractor`: mocked `LlmClient` returns fixed JSON → asserts mapping to `ScrapedEvent[]`.
- `CurrencyNormalizer`: CHF/USD/EUR/none → asserts EUR amounts via the configured rates.
- `EventImportService`: asserts upsert (published, source_url, organizer category + primary
  venue, EUR prices), idempotency on re-import, and that `source_url=null` events are untouched.
- `EventScraperService` with a fake extractor: end-to-end upsert for one organizer.
- `events:scrape` command test with a fake extractor (all + `--organizer=`).

## 7. Pilot & rollout

- Build the engine TDD. No-Guru (first non-rejected) has **no events** → unsuitable; pick a
  pilot organizer from the 109 that has a real structured events list, prove the full pipeline
  live, then run `events:scrape` across all 109 non-rejected organizers.
- Live AI pass requires `ANTHROPIC_API_KEY`.

## 8. Out of scope (later)

Translation of scraped content (AI i18n), de-duplication across organizers, image download for
event photos (will reuse the `organizers/{slug}/events/{event-slug}/` storage convention noted
on `Organizer::imageDirectory()`), and a scraper admin dashboard.
