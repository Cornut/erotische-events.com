<?php

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;

it('lets an organizer owner update their own event but not another', function () {
    $owner = User::factory()->create(['role' => UserRole::Organizer]);
    $organizer = Organizer::factory()->approved()->create(['owner_user_id' => $owner->id]);
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $stranger = User::factory()->create(['role' => UserRole::Organizer]);

    expect($owner->can('update', $event))->toBeTrue()
        ->and($stranger->can('update', $event))->toBeFalse();
});

it('lets an admin update any event', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();
    expect($admin->can('update', $event))->toBeTrue();
});

it('lets an owner update their organizer profile and admin any', function () {
    $owner = User::factory()->create(['role' => UserRole::Organizer]);
    $organizer = Organizer::factory()->create(['owner_user_id' => $owner->id]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect($owner->can('update', $organizer))->toBeTrue()
        ->and($admin->can('update', $organizer))->toBeTrue();
});
