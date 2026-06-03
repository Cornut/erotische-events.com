<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Tracking\ClickTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoController extends Controller
{
    public function redirect(Request $request, Event $event, ClickTrackingService $tracker): RedirectResponse
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $tracker->record(
            $event,
            $request->ip(),
            $request->userAgent(),
            $request->headers->get('referer'),
        );

        return redirect()->away($event->booking_url);
    }
}
