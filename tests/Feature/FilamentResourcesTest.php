<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('lists each catalog resource index for an admin', function (string $path) {
    $this->actingAs($this->admin)->get($path)->assertSuccessful();
})->with([
    '/admin/organizers',
    '/admin/venues',
    '/admin/teachers',
    '/admin/categories',
    '/admin/tags',
    '/admin/events',
]);
