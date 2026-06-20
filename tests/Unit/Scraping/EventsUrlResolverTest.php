<?php

use App\Models\Organizer;
use App\Scraping\EventsUrlResolver;
use Tests\TestCase;

uses(TestCase::class);

it('prefers events_url then website candidate paths', function () {
    $o = Organizer::factory()->make(['owner_user_id' => 1, 'website' => 'https://x.de', 'events_url' => 'https://x.de/termine']);
    $candidates = (new EventsUrlResolver)->candidates($o);

    expect($candidates[0])->toBe('https://x.de/termine')
        ->and($candidates)->toContain('https://x.de/seminare');
});

it('includes every events_url before website candidate paths', function () {
    $o = Organizer::factory()->make([
        'owner_user_id' => 1,
        'website' => 'https://x.de',
        'events_url' => "https://x.de/termine\nhttps://x.de/workshops",
    ]);
    $candidates = (new EventsUrlResolver)->candidates($o);

    expect($candidates[0])->toBe('https://x.de/termine')
        ->and($candidates[1])->toBe('https://x.de/workshops')
        ->and($candidates)->toContain('https://x.de/seminare');
});

it('falls back to website candidate paths when events_url is null', function () {
    $o = Organizer::factory()->make(['owner_user_id' => 1, 'website' => 'https://x.de', 'events_url' => null]);
    $candidates = (new EventsUrlResolver)->candidates($o);

    expect($candidates[0])->toBe('https://x.de/termine')
        ->and($candidates)->toContain('https://x.de');
});
