<?php

use App\Scraping\Extractors\LlmEventExtractor;
use App\Scraping\Llm\AnthropicLlmClient;
use App\Scraping\Llm\LlmClient;
use Tests\TestCase;

uses(TestCase::class);

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
    $extractor = new LlmEventExtractor(new AnthropicLlmClient);

    expect($extractor->extract('<html></html>', 'https://x.de'))->toBe([]);
});
