<?php

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function makeOrganizerUser(): array
{
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    $org = Organizer::factory()->create(['owner_user_id' => $user->id]);

    return [$user, $org];
}

it('redirects a non-organizer away from the organizer area', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($user)
        ->get('/organizer/dashboard')
        ->assertRedirect(route('organizer.register'));
});

it('shows the dashboard and stammdaten to the organizer', function () {
    [$user] = makeOrganizerUser();

    $this->actingAs($user)->get('/organizer/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Organizer/Dashboard'));

    $this->actingAs($user)->get('/organizer/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Organizer/Profile/Edit'));
});

it('lets the organizer update their stammdaten', function () {
    [$user, $org] = makeOrganizerUser();

    $this->actingAs($user)
        ->put('/organizer/profile', [
            'company_name' => 'Neue Firma GmbH',
            'city' => 'Berlin',
            'country' => 'DE',
        ])
        ->assertRedirect();

    expect($org->fresh()->company_name)->toBe('Neue Firma GmbH')
        ->and($org->fresh()->city)->toBe('Berlin');
});

it('creates a venue scoped to the organizer and geocodes it', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '52.5200', 'lon' => '13.4050'],
        ]),
    ]);

    [$user, $org] = makeOrganizerUser();

    $this->actingAs($user)->get('/organizer/venues')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Organizer/Venues/Index'));

    $this->actingAs($user)
        ->post('/organizer/venues', [
            'name' => 'Studio Mitte',
            'street' => 'Hauptstr. 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'DE',
        ])
        ->assertRedirect(route('organizer.venues.index'));

    $venue = $org->venues()->first();
    expect($venue)->not->toBeNull()
        ->and($venue->name)->toBe('Studio Mitte')
        ->and($venue->organizer_id)->toBe($org->id)
        ->and((float) $venue->latitude)->toBe(52.52);
});

it('forbids editing another organizer venue', function () {
    [$userA] = makeOrganizerUser();
    [, $orgB] = makeOrganizerUser();
    $foreignVenue = Venue::factory()->create(['organizer_id' => $orgB->id]);

    $this->actingAs($userA)->get("/organizer/venues/{$foreignVenue->id}/edit")
        ->assertForbidden();
});

it('adds teachers to the shared pool and dedupes by slug', function () {
    [$user] = makeOrganizerUser();

    $this->actingAs($user)->get('/organizer/teachers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Organizer/Teachers/Index'));

    $this->actingAs($user)->post('/organizer/teachers', ['name' => 'Anand Lehrer'])->assertRedirect();
    $this->actingAs($user)->post('/organizer/teachers', ['name' => 'Anand Lehrer'])->assertRedirect();

    expect(Teacher::where('name', 'Anand Lehrer')->count())->toBe(1);
});

it('creates a draft event with relations and a regular price, then submits it', function () {
    [$user, $org] = makeOrganizerUser();
    $venue = Venue::factory()->create(['organizer_id' => $org->id]);
    $category = Category::create(['slug' => 'tantra', 'name_de' => 'Tantra', 'name_en' => 'Tantra']);
    $teacher = Teacher::factory()->create();

    // Create form exposes the options the organizer may pick from.
    $this->actingAs($user)->get('/organizer/events/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Organizer/Events/Create')
            ->has('options.venues')
            ->has('options.categories')
            ->has('options.teachers'));

    $this->actingAs($user)
        ->post('/organizer/events', [
            'title' => 'Tantra Abend',
            'short_description' => 'Kurz',
            'start_date' => '2026-09-01T19:00',
            'booking_url' => 'https://example.com/tickets',
            'venue_id' => $venue->id,
            'categories' => [$category->slug],
            'teachers' => [$teacher->id],
            'price_amount' => 49.5,
            'price_currency' => 'EUR',
        ])
        ->assertRedirect(route('organizer.events.index'));

    $event = $org->events()->first();
    expect($event)->not->toBeNull()
        ->and($event->status)->toBe(EventStatus::Draft)
        ->and($event->venue_id)->toBe($venue->id)
        ->and($event->categories()->pluck('slug')->all())->toContain($category->slug)
        ->and($event->teachers()->pluck('teachers.id')->all())->toContain($teacher->id)
        ->and((float) $event->prices()->where('type', 'regular')->first()->amount)->toBe(49.5);

    // Submit for review.
    $this->actingAs($user)->post(route('organizer.events.submit', $event))->assertRedirect();
    expect($event->fresh()->status)->toBe(EventStatus::PendingReview);
});

it('rejects an event referencing a venue the organizer does not own', function () {
    [$user] = makeOrganizerUser();
    [, $orgB] = makeOrganizerUser();
    $foreignVenue = Venue::factory()->create(['organizer_id' => $orgB->id]);

    $this->actingAs($user)
        ->from('/organizer/events/create')
        ->post('/organizer/events', [
            'title' => 'Hack',
            'start_date' => '2026-09-01T19:00',
            'booking_url' => 'https://example.com/x',
            'venue_id' => $foreignVenue->id,
        ])
        ->assertSessionHasErrors('venue_id');
});

it('forbids submitting another organizer event', function () {
    [$userA] = makeOrganizerUser();
    [, $orgB] = makeOrganizerUser();
    $foreignEvent = Event::factory()->create(['organizer_id' => $orgB->id, 'status' => EventStatus::Draft]);

    $this->actingAs($userA)->post(route('organizer.events.submit', $foreignEvent))
        ->assertForbidden();
});
