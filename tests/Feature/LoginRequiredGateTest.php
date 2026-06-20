<?php

use App\Models\Setting;
use App\Models\User;

it('redirects guests to login when login_required is enabled', function () {
    Setting::put('login_required', '1');

    $this->get('/events')->assertRedirect(route('login'));
});

it('still allows the login page itself when login_required is enabled', function () {
    Setting::put('login_required', '1');

    $this->get('/login')->assertOk();
});

it('lets authenticated users through when login_required is enabled', function () {
    Setting::put('login_required', '1');

    $this->actingAs(User::factory()->create())->get('/events')->assertOk();
});

it('does not gate the site when login_required is disabled', function () {
    Setting::put('login_required', '0');

    $this->get('/events')->assertOk();
});
