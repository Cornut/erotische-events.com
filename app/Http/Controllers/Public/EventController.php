<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::published()
            ->with(['organizer', 'venue'])
            ->orderBy('start_date')
            ->paginate(12);

        return Inertia::render('Public/Events/Index', [
            'events' => $events,
        ]);
    }

    public function show(string $slug): Response
    {
        $event = Event::published()
            ->where('slug', $slug)
            ->with(['organizer', 'venue', 'teachers', 'categories', 'tags', 'prices'])
            ->firstOrFail();

        return Inertia::render('Public/Events/Show', [
            'event' => $event,
        ]);
    }
}
