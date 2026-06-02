<?php

use App\Models\Organizer;
use App\Models\Venue;

it('creates a venue belonging to an organizer with coordinates', function () {
    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->id, 'latitude' => 52.52, 'longitude' => 13.405]);

    expect($venue->organizer->is($organizer))->toBeTrue()
        ->and((float) $venue->latitude)->toBe(52.52)
        ->and($venue->images)->toBeArray();
});
