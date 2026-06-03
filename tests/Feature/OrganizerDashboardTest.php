<?php

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;

function organizerUser(): array
{
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    $organizer = Organizer::factory()->approved()->create(['owner_user_id' => $user->id]);

    return [$user, $organizer];
}

it('shows the dashboard with only the organizer own events', function () {
    [$user, $organizer] = organizerUser();
    Event::factory()->create(['organizer_id' => $organizer->id, 'title' => 'Mine']);
    Event::factory()->create(['title' => 'Someone else']);

    $this->actingAs($user)->get('/organizer/dashboard')->assertSuccessful();
});

it('lets an organizer create a draft event', function () {
    [$user, $organizer] = organizerUser();

    $response = $this->actingAs($user)->post('/organizer/events', [
        'title' => 'New Workshop',
        'booking_url' => 'https://example.com/book',
        'start_date' => now()->addWeek()->toDateTimeString(),
    ]);

    $response->assertRedirect();
    $event = Event::where('title', 'New Workshop')->firstOrFail();
    expect($event->status)->toBe(EventStatus::Draft)
        ->and($event->organizer_id)->toBe($organizer->id);
});

it('lets an organizer submit their draft for review', function () {
    [$user, $organizer] = organizerUser();
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'status' => EventStatus::Draft]);

    $this->actingAs($user)->post("/organizer/events/{$event->id}/submit")->assertRedirect();
    expect($event->fresh()->status)->toBe(EventStatus::PendingReview);
});

it('forbids submitting another organizer event', function () {
    [$user] = organizerUser();
    $foreign = Event::factory()->create(['status' => EventStatus::Draft]);

    $this->actingAs($user)->post("/organizer/events/{$foreign->id}/submit")->assertForbidden();
});
