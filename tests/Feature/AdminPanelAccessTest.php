<?php

use App\Enums\UserRole;
use App\Models\User;

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});

it('forbids non-admin users from the admin panel', function () {
    $user = User::factory()->create(['role' => UserRole::User]);
    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows admins into the admin panel', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});
