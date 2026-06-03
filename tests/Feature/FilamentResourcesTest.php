<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Tag;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Venue;

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

it('renders each catalog resource create page for an admin', function (string $path) {
    $this->actingAs($this->admin)->get($path)->assertSuccessful();
})->with([
    '/admin/organizers/create',
    '/admin/venues/create',
    '/admin/teachers/create',
    '/admin/categories/create',
    '/admin/tags/create',
    '/admin/events/create',
]);

it('renders the edit page for each resource with real records', function () {
    $this->actingAs($this->admin);

    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->id]);
    $teacher = Teacher::factory()->create();
    $category = Category::create(['slug' => 'tantra', 'name_de' => 'Tantra', 'name_en' => 'Tantra']);
    $tag = Tag::create(['name' => 'Couples', 'slug' => 'couples']);
    $event = Event::factory()->create([
        'organizer_id' => $organizer->id,
        'venue_id' => $venue->id,
        'audience' => ['couples', 'everyone'],
        'languages' => ['de', 'en'],
    ]);

    $this->get("/admin/organizers/{$organizer->id}/edit")->assertSuccessful();
    $this->get("/admin/venues/{$venue->id}/edit")->assertSuccessful();
    $this->get("/admin/teachers/{$teacher->id}/edit")->assertSuccessful();
    $this->get("/admin/categories/{$category->id}/edit")->assertSuccessful();
    $this->get("/admin/tags/{$tag->id}/edit")->assertSuccessful();
    $this->get("/admin/events/{$event->id}/edit")->assertSuccessful();
});
