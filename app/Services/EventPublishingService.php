<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Exceptions\InvalidEventTransitionException;
use App\Models\Event;

class EventPublishingService
{
    public function submit(Event $event): Event
    {
        return $this->transition($event, [EventStatus::Draft, EventStatus::Rejected], EventStatus::PendingReview);
    }

    public function publish(Event $event): Event
    {
        return $this->transition($event, [EventStatus::PendingReview], EventStatus::Published);
    }

    public function reject(Event $event): Event
    {
        return $this->transition($event, [EventStatus::PendingReview], EventStatus::Rejected);
    }

    public function archive(Event $event): Event
    {
        return $this->transition($event, [EventStatus::Published], EventStatus::Archived);
    }

    /**
     * @param  array<EventStatus>  $allowedFrom
     */
    private function transition(Event $event, array $allowedFrom, EventStatus $to): Event
    {
        if (! in_array($event->status, $allowedFrom, true)) {
            throw new InvalidEventTransitionException(
                "Cannot transition event {$event->id} from {$event->status->value} to {$to->value}."
            );
        }

        $event->update(['status' => $to]);

        return $event;
    }
}
