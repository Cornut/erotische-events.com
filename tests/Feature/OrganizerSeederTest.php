<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use Database\Seeders\OrganizerSeeder;

it('seeds the curated organizers owned by the admin', function () {
    $this->seed(OrganizerSeeder::class);

    expect(Organizer::count())->toBeGreaterThan(200);

    $o = Organizer::where('slug', 'no-guru-net')->firstOrFail();
    expect($o->company_name)->toBe('No-Guru Institut Tantra')
        ->and($o->website)->toBe('https://no-guru.net')
        ->and($o->verification_status)->toBe(OrganizerVerificationStatus::Approved)
        ->and($o->owner->email)->toBe('admin@erotische-events.com');
});

it('is idempotent when run twice', function () {
    $this->seed(OrganizerSeeder::class);
    $count = Organizer::count();
    $this->seed(OrganizerSeeder::class);

    expect(Organizer::count())->toBe($count);
});
