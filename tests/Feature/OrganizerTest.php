<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Models\User;

it('creates an organizer owned by a user with pending status by default', function () {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create(['owner_user_id' => $user->id]);

    expect($organizer->owner->is($user))->toBeTrue()
        ->and($organizer->verification_status)->toBe(OrganizerVerificationStatus::Pending)
        ->and($organizer->social_links)->toBeArray();
});

it('soft-deletes an organizer', function () {
    $organizer = Organizer::factory()->create();
    $organizer->delete();
    expect(Organizer::count())->toBe(0)->and(Organizer::withTrashed()->count())->toBe(1);
});
