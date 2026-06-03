<?php

use App\Enums\EventStatus;
use App\Exceptions\InvalidEventTransitionException;
use App\Models\Event;
use App\Services\EventPublishingService;

beforeEach(function () {
    $this->service = app(EventPublishingService::class);
});

it('submits a draft for review', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $this->service->submit($event);
    expect($event->fresh()->status)->toBe(EventStatus::PendingReview);
});

it('publishes a pending event', function () {
    $event = Event::factory()->create(['status' => EventStatus::PendingReview]);
    $this->service->publish($event);
    expect($event->fresh()->status)->toBe(EventStatus::Published);
});

it('rejects a pending event', function () {
    $event = Event::factory()->create(['status' => EventStatus::PendingReview]);
    $this->service->reject($event);
    expect($event->fresh()->status)->toBe(EventStatus::Rejected);
});

it('archives a published event', function () {
    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $this->service->archive($event);
    expect($event->fresh()->status)->toBe(EventStatus::Archived);
});

it('forbids publishing a draft directly', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $this->service->publish($event);
})->throws(InvalidEventTransitionException::class);
