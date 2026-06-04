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
