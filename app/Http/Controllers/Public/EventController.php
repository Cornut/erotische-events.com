<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Search\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, SearchService $search): Response
    {
        $filters = $request->only([
            'q', 'category', 'country', 'city',
            'date_from', 'date_to', 'price_min', 'price_max',
            'lat', 'lng', 'radius_km',
        ]);

        return Inertia::render('Public/Events/Index', [
            'events' => $search->search($filters),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'city' => $request->string('city')->toString(),
                'category' => $request->string('category')->toString(),
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $event = Event::published()
            ->where('slug', $slug)
            ->with(['organizer', 'venue', 'teachers', 'categories', 'tags', 'prices'])
            ->firstOrFail();

        // Obfuscate the outbound target: never expose the raw URL to the client;
        // the "Zum Veranstalter" link uses /go/{event} for tracked redirection.
        $event->makeHidden(['booking_url', 'source_url']);

        return Inertia::render('Public/Events/Show', [
            'event' => $event,
        ]);
    }
}
