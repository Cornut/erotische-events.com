<?php

use App\Enums\UserRole;
use App\Models\User;

it('defaults a new user to the user role', function () {
    $user = User::factory()->create();
    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->locale)->toBe('de');
});

it('can create an admin and soft-delete it', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    expect($admin->role)->toBe(UserRole::Admin);

    $admin->delete();
    expect(User::query()->count())->toBe(0)
        ->and(User::withTrashed()->count())->toBe(1);
});
