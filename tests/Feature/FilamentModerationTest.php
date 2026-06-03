<?php

use App\Enums\EventStatus;
use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Organizers\Pages\ListOrganizers;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
});

it('approves a pending organizer via the table action', function () {
    $organizer = Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Pending]);

    livewire(ListOrganizers::class)
        ->callTableAction('approve', $organizer);

    expect($organizer->fresh()->verification_status)->toBe(OrganizerVerificationStatus::Approved);
});

it('rejects a pending organizer via the table action', function () {
    $organizer = Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Pending]);

    livewire(ListOrganizers::class)
        ->callTableAction('reject', $organizer);

    expect($organizer->fresh()->verification_status)->toBe(OrganizerVerificationStatus::Rejected);
});

it('publishes a pending event via the table action', function () {
    $event = Event::factory()->create(['status' => EventStatus::PendingReview]);

    livewire(ListEvents::class)
        ->callTableAction('publish', $event);

    expect($event->fresh()->status)->toBe(EventStatus::Published);
});
