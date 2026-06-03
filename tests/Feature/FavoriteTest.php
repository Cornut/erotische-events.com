<?php

use App\Models\Event;
use App\Models\User;

it('lets an authenticated user favorite and unfavorite an event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->published()->create();

    $this->actingAs($user)->post("/events/{$event->id}/favorite")->assertRedirect();
    expect($user->favorites()->whereKey($event->id)->exists())->toBeTrue();

    $this->actingAs($user)->post("/events/{$event->id}/favorite")->assertRedirect();
    expect($user->favorites()->whereKey($event->id)->exists())->toBeFalse();
});

it('redirects a guest trying to favorite to login', function () {
    $event = Event::factory()->published()->create();
    $this->post("/events/{$event->id}/favorite")->assertRedirect('/login');
});

it('shows only the current user favorites on the favorites page', function () {
    $user = User::factory()->create();
    $mine = Event::factory()->published()->create();
    $other = Event::factory()->published()->create();
    $user->favorites()->attach($mine);

    User::factory()->create()->favorites()->attach($other);

    $this->actingAs($user)->get('/favorites')->assertSuccessful();
    expect($user->favorites()->count())->toBe(1)
        ->and($user->favorites()->first()->is($mine))->toBeTrue();
});
