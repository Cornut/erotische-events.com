<?php

use App\Models\Organizer;
use Database\Seeders\OrganizerSeeder;

it('seeds events_url from the source listing url', function () {
    $this->seed(OrganizerSeeder::class);

    $o = Organizer::where('slug', 'secret-of-tantra-de')->firstOrFail();
    expect($o->events_url)->not->toBeNull()
        ->and($o->events_url)->toContain('secret-of-tantra.de');

    $s = Organizer::where('slug', 'shakti-sangha-de')->firstOrFail();
    expect($s->events_url)->toContain('/tantra-seminare');
});
