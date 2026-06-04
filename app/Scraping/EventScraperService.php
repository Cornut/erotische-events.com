<?php

namespace App\Scraping;

use App\Models\Organizer;
use App\Scraping\Extractors\EventExtractor;

class EventScraperService
{
    /**
     * @param  array<int, EventExtractor>  $extractors  ordered: structured-data first, LLM fallback last
     */
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly array $extractors,
        private readonly EventImportService $importer,
        private readonly EventsUrlResolver $resolver,
    ) {}

    /**
     * @return array{created: int, updated: int, url: ?string}
     */
    public function scrape(Organizer $organizer): array
    {
        $result = ['created' => 0, 'updated' => 0, 'url' => null];

        foreach ($this->resolver->candidates($organizer) as $url) {
            $html = $this->fetcher->get($url);
            if ($html === null) {
                continue;
            }

            foreach ($this->extractors as $extractor) {
                $events = $extractor->extract($html, $url);
                if ($events !== []) {
                    $imported = $this->importer->import($organizer, $events);
                    $result = [...$imported, 'url' => $url];
                    break 2;
                }
            }
        }

        $organizer->forceFill(['last_scraped_at' => now()])->save();

        return $result;
    }
}
