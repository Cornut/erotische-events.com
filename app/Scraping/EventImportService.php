<?php

namespace App\Scraping;

use App\Enums\EventStatus;
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
        $venueId = $organizer->venues()->value('id');
        $categoryId = $organizer->category ? Category::where('slug', $organizer->category)->value('id') : null;

        foreach ($events as $scraped) {
            $existing = Event::query()
                ->where('organizer_id', $organizer->id)
                ->whereNotNull('source_url')
                ->where('source_url', $scraped->sourceUrl)
                ->first();

            $isNew = $existing === null;
            $event = $existing ?? new Event(['organizer_id' => $organizer->id]);

            $event->fill([
                'title' => $scraped->title,
                'slug' => $event->slug ?: Str::slug($scraped->title).'-'.Str::lower(Str::random(6)),
                'short_description' => $scraped->description ? Str::limit($scraped->description, 250) : null,
                'long_description' => $scraped->description,
                'start_date' => $scraped->startDate,
                'end_date' => $scraped->endDate,
                'status' => EventStatus::Published,
                'currency' => 'EUR',
                'booking_url' => $scraped->bookingUrl,
                'source_url' => $scraped->sourceUrl,
                'venue_id' => $event->venue_id ?: $venueId,
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

            $isNew ? $created++ : $updated++;
        }

        return ['created' => $created, 'updated' => $updated];
    }
}
