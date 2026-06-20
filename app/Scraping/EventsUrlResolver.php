<?php

namespace App\Scraping;

use App\Models\Organizer;

class EventsUrlResolver
{
    /**
     * @return array<int, string> Ordered candidate listing URLs to try.
     */
    public function candidates(Organizer $organizer): array
    {
        $urls = [];

        foreach ($organizer->eventUrls() as $eventUrl) {
            $urls[] = $eventUrl;
        }

        $base = rtrim((string) $organizer->website, '/');
        if ($base !== '') {
            foreach (config('scraping.candidate_paths', []) as $path) {
                $urls[] = $base.$path;
            }
            $urls[] = $base;
        }

        return array_values(array_unique($urls));
    }
}
