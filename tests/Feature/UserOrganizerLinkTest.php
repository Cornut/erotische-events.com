<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Organizer;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    // The acting admin doubles as the "parking" owner for unclaimed organizers.
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('prefills the linked organizer on the edit form', function () {
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    $org = Organizer::factory()->create(['owner_user_id' => $user->id]);

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormSet(['organizer_id' => $org->id]);
});

it('assigns an unclaimed organizer to a user from the user form', function () {
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    $org = Organizer::factory()->create(['owner_user_id' => $this->admin->id]); // unclaimed

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['organizer_id' => $org->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($org->fresh()->owner_user_id)->toBe($user->id);
});

it('reassigns ownership and re-parks the previously owned organizer under the admin', function () {
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    $oldOrg = Organizer::factory()->create(['owner_user_id' => $user->id]);
    $newOrg = Organizer::factory()->create(['owner_user_id' => $this->admin->id]);

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['organizer_id' => $newOrg->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($newOrg->fresh()->owner_user_id)->toBe($user->id)
        ->and($oldOrg->fresh()->owner_user_id)->toBe($this->admin->id);
});

it('re-parks the organizer under the admin when the link is cleared', function () {
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    $org = Organizer::factory()->create(['owner_user_id' => $user->id]);

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['organizer_id' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($org->fresh()->owner_user_id)->toBe($this->admin->id);
});

it('links an organizer when creating a user', function () {
    $org = Organizer::factory()->create(['owner_user_id' => $this->admin->id]);

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Neue:r Veranstalter:in',
            'email' => 'neu-org@example.com',
            'role' => UserRole::Organizer->value,
            'password' => 'password123',
            'locale' => 'de',
            'organizer_id' => $org->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'neu-org@example.com')->first();
    expect($org->fresh()->owner_user_id)->toBe($user->id);
});

it('never bulk-reassigns an admin\'s parked organizers', function () {
    $otherAdmin = User::factory()->create(['role' => UserRole::Admin]);
    $parked = Organizer::factory()->count(3)->create(['owner_user_id' => $otherAdmin->id]);

    livewire(EditUser::class, ['record' => $otherAdmin->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    foreach ($parked as $org) {
        expect($org->fresh()->owner_user_id)->toBe($otherAdmin->id);
    }
});
