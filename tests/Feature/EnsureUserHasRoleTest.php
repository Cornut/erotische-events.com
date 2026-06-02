<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'role:admin'])->get('/_test/admin-only', fn () => 'ok');
});

it('blocks a non-admin from a role:admin route', function () {
    $user = User::factory()->create(['role' => UserRole::User]);
    $this->actingAs($user)->get('/_test/admin-only')->assertForbidden();
});

it('allows an admin through a role:admin route', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get('/_test/admin-only')->assertOk();
});
