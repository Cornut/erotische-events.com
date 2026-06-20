<?php

namespace App\Search;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    /**
     * Full-text query (when `q` is set) runs through Scout/Meilisearch; the
     * structured filters and geo (bounding box) are applied to the underlying
     * database query. Without a text query everything runs against the database,
     * ordered by date.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $term = trim((string) ($filters['q'] ?? ''));

        if ($term !== '') {
            return Event::search($term)
                ->query(fn (Builder $query) => $this->applyFilters(
                    $query->published()->with(['organizer', 'venue']),
                    $filters,
                ))
                ->paginate(12)
                ->withQueryString();
        }

        $query = Event::published()->with(['organizer', 'venue']);
        $this->applyFilters($query, $filters);

        return $query->orderBy('start_date')->paginate(12)->withQueryString();
    }

    /**
     * Apply the structured (non-text) filters to an Eloquent query.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['teacher'])) {
            $query->whereHas('teachers', fn ($q) => $q->where('name', 'like', '%'.$filters['teacher'].'%'));
        }

        if (! empty($filters['country'])) {
            $query->whereHas('venue', fn ($q) => $q->where('country', $filters['country']));
        }

        if (! empty($filters['countries'])) {
            $countries = array_values(array_filter((array) $filters['countries']));
            if ($countries !== []) {
                $query->whereHas('venue', fn ($q) => $q->whereIn('country', $countries));
            }
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

        return $query;
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
