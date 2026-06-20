<?php

use App\Models\Event;
use App\Models\Organizer;
use App\Scraping\HttpPageFetcher;
use App\Scraping\Llm\AnthropicLlmClient;

/**
 * Guards the cron path the user will rely on once ANTHROPIC_API_KEY is set:
 * `events:scrape` -> container-resolved EventScraperService (real provider wiring)
 * -> auto-discovery -> structured extractors fail on plain text -> LLM extractor
 * (wrapping the real AnthropicLlmClient) yields events -> imported.
 */
it('fires the LLM extractor through the scheduled scrape command when the client returns events', function () {
    $org = Organizer::factory()->approved()->create([
        'slug' => 'llm-org',
        'website' => 'https://x.de',
        'events_url' => 'https://x.de/termine',
    ]);

    // Plain-text page: no JSON-LD, no iCal -> structured extractors return nothing.
    $fetcher = Mockery::mock(HttpPageFetcher::class);
    $fetcher->shouldReceive('get')->andReturnUsing(
        fn ($url) => $url === 'https://x.de/termine' ? '<html>plain text, no structured data</html>' : null,
    );
    $this->app->instance(HttpPageFetcher::class, $fetcher);

    // Stand in for the key-backed Anthropic client.
    $llm = Mockery::mock(AnthropicLlmClient::class);
    $llm->shouldReceive('extractEvents')->andReturn([[
        'title' => 'LLM Found Event',
        'start_date' => '2026-12-01 19:00',
        'source_url' => 'https://x.de/e/llm-1',
    ]]);
    $llm->shouldReceive('findEventUrls')->andReturn([]);
    $this->app->instance(AnthropicLlmClient::class, $llm);

    $this->artisan('events:scrape', ['--organizer' => 'llm-org'])->assertSuccessful();

    expect(Event::where('source_url', 'https://x.de/e/llm-1')->exists())->toBeTrue();
});
