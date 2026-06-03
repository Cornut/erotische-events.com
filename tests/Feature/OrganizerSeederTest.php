<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Models\Venue;
use Database\Seeders\OrganizerSeeder;

it('seeds the curated organizers owned by the admin', function () {
    $this->seed(OrganizerSeeder::class);

    expect(Organizer::count())->toBeGreaterThan(200);

    $o = Organizer::where('slug', 'no-guru-net')->firstOrFail();
    expect($o->company_name)->toBe('No-Guru Institut Tantra')
        ->and($o->website)->toBe('https://no-guru.net')
        ->and($o->category)->toBe('tantra')
        ->and($o->verification_status)->toBe(OrganizerVerificationStatus::Approved)
        ->and($o->owner->email)->toBe('admin@erotische-events.com');
});

it('applies the Impressum Stammdaten and primary venue during seeding', function () {
    $this->seed(OrganizerSeeder::class);

    $o = Organizer::where('slug', 'no-guru-net')->firstOrFail();
    expect($o->legal_name)->toBe('Matthias Möbius')
        ->and($o->city)->toBe('Hamburg')
        ->and($o->street)->toBe('Kleine Rainstr. 3');

    expect(Venue::where('slug', 'no-guru-net-hauptstandort')->exists())->toBeTrue();
});

it('is idempotent when run twice', function () {
    $this->seed(OrganizerSeeder::class);
    $count = Organizer::count();
    $this->seed(OrganizerSeeder::class);

    expect(Organizer::count())->toBe($count);
});
