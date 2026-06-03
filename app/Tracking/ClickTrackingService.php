<?php

namespace App\Tracking;

use App\Enums\DeviceType;
use App\Models\Event;
use App\Models\EventClick;

class ClickTrackingService
{
    public function __construct(private readonly GeoIpResolver $geoIp) {}

    /**
     * Record an outbound click. The IP is used only to resolve a country and
     * is never stored.
     */
    public function record(Event $event, ?string $ip, ?string $userAgent, ?string $referrer): EventClick
    {
        return EventClick::create([
            'event_id' => $event->id,
            'organizer_id' => $event->organizer_id,
            'clicked_at' => now(),
            'country' => $this->geoIp->countryFor($ip),
            'device_type' => DeviceType::fromUserAgent($userAgent),
            'referrer' => $referrer,
        ]);
    }
}
