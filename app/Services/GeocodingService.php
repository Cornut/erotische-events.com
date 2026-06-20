<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Facades\Http;

/**
 * Resolves venue addresses to GPS coordinates via the OpenStreetMap Nominatim
 * API (free, no key — usage policy: <= 1 request/second, identifying User-Agent).
 */
class GeocodingService
{
    /**
     * Geocode a single venue from its address; stores latitude/longitude on success.
     */
    public function geocodeVenue(Venue $venue): bool
    {
        $query = $this->addressQuery($venue);
        if ($query === '') {
            return false;
        }

        $coords = $this->lookup($query, $venue->country);
        if ($coords === null) {
            return false;
        }

        $venue->forceFill([
            'latitude' => $coords['lat'],
            'longitude' => $coords['lng'],
        ])->save();

        return true;
    }

    private function addressQuery(Venue $venue): string
    {
        return collect([$venue->street, $venue->postal_code, $venue->city, $venue->region])
            ->map(fn ($p) => trim((string) $p))
            ->filter()
            ->implode(', ');
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function lookup(string $query, ?string $countryCode = null): ?array
    {
        $params = [
            'q' => $query,
            'format' => 'jsonv2',
            'limit' => 1,
        ];
        if ($countryCode) {
            $params['countrycodes'] = strtolower($countryCode);
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'Laravel').' (venue geocoder)',
            ])->timeout(15)->get('https://nominatim.openstreetmap.org/search', $params);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $first = $response->json()[0] ?? null;
        if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
            return null;
        }

        return ['lat' => (float) $first['lat'], 'lng' => (float) $first['lon']];
    }
}
