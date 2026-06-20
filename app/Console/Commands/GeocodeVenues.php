<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeVenues extends Command
{
    protected $signature = 'venues:geocode {--all : Re-geocode venues that already have coordinates}';

    protected $description = 'Resolve GPS coordinates for venues from their address (OpenStreetMap Nominatim).';

    public function handle(GeocodingService $geocoder): int
    {
        $query = Venue::query()->whereNotNull('city');

        if (! $this->option('all')) {
            $query->where(fn ($q) => $q->whereNull('latitude')->orWhereNull('longitude'));
        }

        $venues = $query->get();
        $done = 0;

        foreach ($venues as $venue) {
            if ($geocoder->geocodeVenue($venue)) {
                $done++;
                $this->line("ok: {$venue->name} — {$venue->latitude},{$venue->longitude}");
            } else {
                $this->warn("skip: {$venue->name} ({$venue->city})");
            }

            usleep(1_100_000); // respect Nominatim's 1 req/s policy
        }

        $this->info("Done. Geocoded {$done}/{$venues->count()} venue(s).");

        return self::SUCCESS;
    }
}
