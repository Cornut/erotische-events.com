<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds exactly one admin user', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@erotische-events.com')->firstOrFail();
    expect($admin->role)->toBe(UserRole::Admin);
});
