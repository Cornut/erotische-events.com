<?php

use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;

it('lets an authenticated user register an organizer profile as pending', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $response = $this->actingAs($user)->post('/organizer/register', [
        'company_name' => 'Tantra Berlin',
    ]);

    $response->assertRedirect();
    $organizer = Organizer::where('owner_user_id', $user->id)->firstOrFail();
    expect($organizer->verification_status)->toBe(OrganizerVerificationStatus::Pending)
        ->and($user->fresh()->role)->toBe(UserRole::Organizer);
});

it('requires authentication to register an organizer', function () {
    $this->post('/organizer/register', ['company_name' => 'X'])->assertRedirect('/login');
});
