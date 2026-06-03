<?php

namespace App\Search;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    /**
     * Filter published events. Structured filters and geo (bounding-box) run
     * against the database so the path is reliable and testable; full-text can
     * be swapped to Scout/Meilisearch later via the Searchable model.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $query = Event::published()->with(['organizer', 'venue']);

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('long_description', 'like', $term);
            });
        }

        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['country'])) {
            $query->whereHas('venue', fn ($q) => $q->where('country', $filters['country']));
        }

        if (! empty($filters['city'])) {
            $query->whereHas('venue', fn ($q) => $q->where('city', $filters['city']));
        }

        if (! empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        if (isset($filters['price_min'])) {
            $query->whereHas('prices', fn ($q) => $q->where('amount', '>=', $filters['price_min']));
        }

        if (isset($filters['price_max'])) {
            $query->whereHas('prices', fn ($q) => $q->where('amount', '<=', $filters['price_max']));
        }

        if (isset($filters['lat'], $filters['lng'], $filters['radius_km'])) {
            [$latMin, $latMax, $lngMin, $lngMax] = $this->boundingBox(
                (float) $filters['lat'],
                (float) $filters['lng'],
                (float) $filters['radius_km'],
            );

            $query->whereHas('venue', function ($q) use ($latMin, $latMax, $lngMin, $lngMax) {
                $q->whereBetween('latitude', [$latMin, $latMax])
                    ->whereBetween('longitude', [$lngMin, $lngMax]);
            });
        }

        return $query->orderBy('start_date')->paginate(12)->withQueryString();
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float} [latMin, latMax, lngMin, lngMax]
     */
    private function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(cos(deg2rad($lat)), 0.01));

        return [$lat - $latDelta, $lat + $latDelta, $lng - $lngDelta, $lng + $lngDelta];
    }
}
