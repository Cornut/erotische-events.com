<?php

use App\Enums\UserRole;
use App\Models\User;

it('registers a new user with the default user role and de locale', function () {
    $response = $this->post('/register', [
        'name' => 'Test Person',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect();
    $user = User::where('email', 'test@example.com')->firstOrFail();
    expect($user->role)->toBe(UserRole::User)
        ->and($user->locale)->toBe('de');
});
