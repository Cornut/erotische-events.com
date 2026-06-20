<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use App\Search\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, SearchService $search): Response
    {
        $filters = $request->only([
            'q', 'category', 'teacher', 'country', 'countries', 'city',
            'date_from', 'date_to', 'price_min', 'price_max',
            'lat', 'lng', 'radius_km',
        ]);

        $events = $search->search($filters);

        // Flag each event with whether the current user has favorited it.
        $favoriteIds = $request->user()?->favorites()->pluck('events.id')->all() ?? [];
        $events->through(function (Event $event) use ($favoriteIds) {
            $event->setAttribute('is_favorited', in_array($event->id, $favoriteIds, true));

            return $event;
        });

        return Inertia::render('Public/Events/Index', [
            'events' => $events,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->string('category')->toString(),
                'teacher' => $request->string('teacher')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'price_min' => $request->string('price_min')->toString(),
                'price_max' => $request->string('price_max')->toString(),
                'countries' => array_values(array_filter((array) $request->input('countries', []))),
                'city' => $request->string('near')->toString(),
                'radius_km' => $request->string('radius_km')->toString(),
                'lat' => $request->string('lat')->toString(),
                'lng' => $request->string('lng')->toString(),
            ],
            // Main (top-level) categories for the filter dropdown.
            'categories' => Category::whereNull('parent_id')
                ->orderBy('position')
                ->orderBy('name_de')
                ->get(['slug', 'name_de']),
            // Countries present in the catalog (for the radius-search country tags).
            'countryOptions' => $this->countryOptions(),
        ]);
    }

    /**
     * Countries present in the catalog, mapped to display names.
     *
     * @return array<int, array{code: string, name: string}>
     */
    private function countryOptions(): array
    {
        $names = [
            'DE' => 'Deutschland', 'AT' => 'Österreich', 'CH' => 'Schweiz',
            'IT' => 'Italien', 'ES' => 'Spanien', 'FR' => 'Frankreich',
            'HR' => 'Kroatien', 'NL' => 'Niederlande', 'BE' => 'Belgien',
            'PL' => 'Polen', 'CZ' => 'Tschechien', 'DK' => 'Dänemark',
            'GB' => 'Großbritannien', 'PT' => 'Portugal', 'GR' => 'Griechenland',
        ];

        return Venue::query()
            ->whereNotNull('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country')
            ->map(fn (string $code) => ['code' => $code, 'name' => $names[$code] ?? $code])
            ->all();
    }

    public function show(Request $request, string $slug): Response
    {
        $event = Event::published()
            ->where('slug', $slug)
            ->with(['organizer', 'venue', 'teachers', 'categories', 'tags', 'prices'])
            ->firstOrFail();

        $event->setAttribute(
            'is_favorited',
            (bool) $request->user()?->favorites()->whereKey($event->id)->exists(),
        );

        // Obfuscate the outbound target: never expose the raw URL to the client;
        // the "Zum Veranstalter" link uses /go/{event} for tracked redirection.
        $event->makeHidden(['booking_url', 'source_url']);

        // When arriving from the calendar, offer a back-link to that month.
        $calendarBackUrl = null;
        if ($request->query('from') === 'calendar') {
            $month = $request->string('month')->toString();
            $calendarBackUrl = '/calendar'.($month !== '' ? '?month='.$month : '');
        }

        // When arriving from a facilitator's page, offer a back-link to that teacher.
        $teacherBackUrl = null;
        if ($request->query('from') === 'teacher') {
            $teacherSlug = $request->string('teacher')->toString();
            $teacherBackUrl = $teacherSlug !== '' ? '/teacher/'.$teacherSlug : null;
        }

        // When arriving from the (possibly filtered) events list, rebuild a back-link
        // that restores those filters. Only whitelisted keys are echoed, so this can
        // never become an open redirect.
        $eventsBackUrl = null;
        if ($request->query('from') === 'events') {
            $params = array_filter(
                $request->only([
                    'q', 'category', 'teacher', 'date_from', 'date_to',
                    'price_min', 'price_max', 'near', 'lat', 'lng', 'radius_km',
                ]),
                fn ($value) => $value !== null && $value !== '',
            );
            $countries = array_values(array_filter((array) $request->input('countries', [])));
            if ($countries !== []) {
                $params['countries'] = $countries;
            }
            $eventsBackUrl = '/events'.($params !== [] ? '?'.http_build_query($params) : '');
        }

        return Inertia::render('Public/Events/Show', [
            'event' => $event,
            'calendarBackUrl' => $calendarBackUrl,
            'teacherBackUrl' => $teacherBackUrl,
            'eventsBackUrl' => $eventsBackUrl,
        ]);
    }
}
