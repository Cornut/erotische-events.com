<?php

use App\Enums\DeviceType;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventClick;
use Illuminate\Support\Facades\Schema;

it('records a click and 302-redirects to the organizer booking url', function () {
    $event = Event::factory()->published()->create(['booking_url' => 'https://organizer.example/tickets']);

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS) Mobile/15E148'])
        ->get("/go/{$event->id}");

    $response->assertRedirect('https://organizer.example/tickets');

    $click = EventClick::firstOrFail();
    expect($click->event_id)->toBe($event->id)
        ->and($click->organizer_id)->toBe($event->organizer_id)
        ->and($click->device_type)->toBe(DeviceType::Mobile)
        ->and($click->country)->toBeNull();
});

it('never stores an ip address', function () {
    expect(Schema::hasColumn('event_clicks', 'ip'))->toBeFalse()
        ->and(Schema::hasColumn('event_clicks', 'ip_address'))->toBeFalse();
});

it('returns 404 when redirecting an unpublished event', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $this->get("/go/{$event->id}")->assertNotFound();
});

it('classifies a desktop user agent', function () {
    $event = Event::factory()->published()->create();
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) Safari/605'])
        ->get("/go/{$event->id}");

    expect(EventClick::latest('id')->first()->device_type)->toBe(DeviceType::Desktop);
});
