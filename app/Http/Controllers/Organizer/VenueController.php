<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Organizer/Venues/Index', [
            'venues' => $request->user()->organizer->venues()
                ->orderBy('name')
                ->get(['id', 'name', 'street', 'city', 'country', 'latitude', 'longitude']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Organizer/Venues/Create');
    }

    public function store(Request $request, GeocodingService $geocoder): RedirectResponse
    {
        $organizer = $request->user()->organizer;
        $data = $this->validateVenue($request);

        $venue = $organizer->venues()->create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
        ]);

        $geocoder->geocodeVenue($venue); // resolve GPS from the address

        return redirect()->route('organizer.venues.index');
    }

    public function edit(Request $request, Venue $venue): Response
    {
        $this->authorizeVenue($request, $venue);

        return Inertia::render('Organizer/Venues/Edit', [
            'venue' => $venue->only([
                'id', 'name', 'street', 'postal_code', 'city', 'region', 'country',
                'description', 'latitude', 'longitude',
            ]),
        ]);
    }

    public function update(Request $request, Venue $venue, GeocodingService $geocoder): RedirectResponse
    {
        $this->authorizeVenue($request, $venue);

        $venue->update($this->validateVenue($request));
        $geocoder->geocodeVenue($venue->refresh()); // re-resolve GPS if the address changed

        return redirect()->route('organizer.venues.index');
    }

    public function destroy(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorizeVenue($request, $venue);
        $venue->delete();

        return back();
    }

    private function authorizeVenue(Request $request, Venue $venue): void
    {
        abort_unless($venue->organizer_id === $request->user()->organizer->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVenue(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
