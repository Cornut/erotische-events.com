<?php

namespace App\Scraping;

use App\Enums\EventStatus;
use App\Jobs\FetchEventLinkTitle;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Teacher;
use Illuminate\Support\Str;

class EventImportService
{
    public function __construct(private readonly CurrencyNormalizer $currency) {}

    /**
     * @param  array<int, ScrapedEvent>  $events
     * @return array{created: int, updated: int}
     */
    public function import(Organizer $organizer, array $events): array
    {
        $created = 0;
        $updated = 0;
        $venues = $organizer->venues()->get();
        $defaultVenueId = $venues->first()?->id;
        $categoryId = $organizer->category ? Category::where('slug', $organizer->category)->value('id') : null;

        foreach ($events as $scraped) {
            $existing = Event::query()
                ->where('organizer_id', $organizer->id)
                ->whereNotNull('source_url')
                ->where('source_url', $scraped->sourceUrl)
                ->first();

            $isNew = $existing === null;
            $event = $existing ?? new Event(['organizer_id' => $organizer->id]);

            // EUR is stored structured on event_prices for comparison/sorting; the
            // original-currency amount is preserved as text in the description.
            $description = $this->descriptionWithOriginalPrices($scraped);

            // Resolve the venue from the event's own address: reuse a matching venue
            // (incl. the organizer's own) or create a new one for a different location.
            $venueId = $this->resolveVenue($organizer, $venues, $scraped) ?? $event->venue_id ?? $defaultVenueId;

            $event->fill([
                'title' => $scraped->title,
                'slug' => $event->slug ?: Str::slug($scraped->title).'-'.Str::lower(Str::random(6)),
                'short_description' => $description ? Str::limit($description, 250) : null,
                'long_description' => $description,
                'start_date' => $scraped->startDate,
                'end_date' => $scraped->endDate,
                'status' => EventStatus::Published,
                'currency' => 'EUR',
                'booking_url' => $scraped->bookingUrl,
                'source_url' => $scraped->sourceUrl,
                'venue_id' => $venueId,
                'languages' => $scraped->languages ?: ['de'],
            ]);
            $event->save();

            // Prices: replace the scraper-managed prices, normalized to EUR.
            $event->prices()->delete();
            foreach ($scraped->prices as $price) {
                $event->prices()->create([
                    'type' => 'regular',
                    'amount' => $this->currency->toEur((float) ($price['amount'] ?? 0), $price['currency'] ?? null),
                    'currency' => 'EUR',
                ]);
            }

            // Category from the organizer's sheet category (if it maps to a known category slug).
            if ($categoryId) {
                $event->categories()->syncWithoutDetaching([$categoryId]);
            }

            // Teachers: global dedup by slug so the same person is ONE record shared
            // across events and organizers (e.g. a teacher appearing at several festivals).
            $teacherIds = [];
            foreach ($scraped->teachers as $name) {
                $teacher = Teacher::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name],
                );
                $teacherIds[] = $teacher->id;
            }
            if ($teacherIds !== []) {
                $event->teachers()->syncWithoutDetaching($teacherIds);
            }

            // Resolve the target page's title in the background (queued) for new
            // events; skipped during tests to keep them network-free.
            if (! app()->runningUnitTests() && ! empty($event->booking_url) && empty($event->booking_title)) {
                FetchEventLinkTitle::dispatch($event->id);
            }

            $isNew ? $created++ : $updated++;
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Resolve which venue an event belongs to from its scraped address.
     *
     * - No scraped location -> null (caller falls back to the organizer default).
     * - Address matches an existing venue (including the organizer's own) -> reuse it.
     * - Otherwise create a new venue under the organizer for that location.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Venue>  $venues  loaded organizer venues (mutated when one is created)
     */
    private function resolveVenue(Organizer $organizer, $venues, ScrapedEvent $scraped): ?int
    {
        $hasStreet = trim((string) $scraped->street) !== '';
        $wantKey = $this->addressKey($scraped->street, $scraped->city);
        if ($wantKey === null) {
            return null; // nothing to locate by
        }

        foreach ($venues as $venue) {
            // Compare on the same granularity the scraped event provides: street+city
            // when we have a street, otherwise city only — so "almost always street+ort"
            // distinguishes locations, while city-only data still reuses a city venue.
            $venueKey = $hasStreet
                ? $this->addressKey($venue->street, $venue->city)
                : $this->addressKey(null, $venue->city);

            if ($venueKey !== null && $venueKey === $wantKey) {
                return $venue->id;
            }
        }

        $name = $scraped->venueName ?: ($scraped->city ?: 'Veranstaltungsort');
        $venue = $organizer->venues()->create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'street' => $scraped->street ?: null,
            'postal_code' => $scraped->postalCode ?: null,
            'city' => $scraped->city ?: null,
            'region' => $scraped->region ?: null,
            'country' => $scraped->country ?: null,
        ]);
        $venues->push($venue); // visible to later events in the same import run

        return $venue->id;
    }

    /**
     * Normalised address key (lowercased, punctuation collapsed) for comparison.
     */
    private function addressKey(?string $street, ?string $city): ?string
    {
        $parts = array_filter([trim((string) $street), trim((string) $city)], fn ($p) => $p !== '');
        if ($parts === []) {
            return null;
        }

        return Str::of(implode(' ', $parts))->lower()->replaceMatches('/[^a-z0-9]+/u', ' ')->trim()->value();
    }

    /**
     * Merge the scraped description with a human-readable note of the prices in
     * their ORIGINAL currency (e.g. "Preis: 420 CHF"). Returns null when there is
     * neither a description nor any price.
     */
    private function descriptionWithOriginalPrices(ScrapedEvent $scraped): ?string
    {
        $parts = [];
        foreach ($scraped->prices as $price) {
            $amount = (float) ($price['amount'] ?? 0);
            $currency = trim((string) ($price['currency'] ?? ''));
            if ($amount <= 0 || $currency === '') {
                continue;
            }
            $parts[] = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.').' '.$currency;
        }

        $note = $parts !== [] ? 'Preis: '.implode(', ', array_unique($parts)) : null;
        $base = $scraped->description !== null ? trim($scraped->description) : '';

        $combined = trim($base.($base !== '' && $note ? "\n\n" : '').($note ?? ''));

        return $combined !== '' ? $combined : null;
    }
}
