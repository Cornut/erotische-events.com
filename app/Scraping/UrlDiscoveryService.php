<?php

namespace App\Scraping;

use App\Models\Organizer;
use App\Scraping\Extractors\EventExtractor;
use App\Scraping\Llm\LlmClient;

/**
 * Discovers an organizer's event-listing / iCal URLs and VALIDATES each candidate
 * with the AI-free extractors (JSON-LD + iCal); only URLs that actually yield events
 * are returned and stored in organizers.scrape_urls so recurring scrapes run without AI.
 *
 * Candidates come from three sources:
 *   1. the LLM reading the seed page (listing/ical URLs),
 *   2. iCal links found directly in the seed HTML,
 *   3. iCal links found one level deeper — on same-domain pages linked from the seed
 *      (e.g. .ics feeds that live on event detail pages).
 */
class UrlDiscoveryService
{
    /**
     * @param  array<int, EventExtractor>  $structuredExtractors  AI-free extractors used for validation
     */
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly LlmClient $llm,
        private readonly array $structuredExtractors,
    ) {}

    /**
     * @return array<int, string> Validated listing/feed URLs (each yields events AI-free).
     */
    public function discover(Organizer $organizer): array
    {
        $seeds = $organizer->eventUrls();
        if ($seeds === [] && ! empty($organizer->website)) {
            $seeds = [rtrim((string) $organizer->website, '/')];
        }
        if ($seeds === []) {
            return [];
        }

        $valid = [];
        foreach ($seeds as $seed) {
            foreach ($this->discoverFromSeed($organizer, $seed) as $url) {
                $valid[] = $url;
            }
        }

        return array_values(array_unique($valid));
    }

    /**
     * @return array<int, string>
     */
    private function discoverFromSeed(Organizer $organizer, string $seed): array
    {
        $html = $this->fetcher->get($seed);
        $website = rtrim((string) $organizer->website, '/');
        if ($html === null && $website !== '' && $website !== rtrim($seed, '/')) {
            $seed = $website;
            $html = $this->fetcher->get($seed);
        }
        if ($html === null) {
            return [];
        }

        $candidates = array_merge(
            $this->llm->findEventUrls($html, $seed),
            $this->icalLinks($html, $seed),
            $this->crawlForIcal($html, $seed),
        );

        $valid = [];
        foreach (array_unique($candidates) as $candidate) {
            if ($this->yieldsEvents($candidate)) {
                $valid[] = $candidate;
            }
        }

        return $valid;
    }

    private function yieldsEvents(string $url): bool
    {
        $body = $this->fetcher->get($url);
        if ($body === null) {
            return false;
        }
        foreach ($this->structuredExtractors as $extractor) {
            if ($extractor->extract($body, $url) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Follow same-domain links from the seed (capped) and collect iCal links on them.
     *
     * @return array<int, string>
     */
    private function crawlForIcal(string $html, string $seed): array
    {
        $found = [];
        $max = (int) config('scraping.max_detail_pages', 25);

        foreach (array_slice(LinkExtractor::sameDomainLinks($html, $seed), 0, $max) as $page) {
            $body = $this->fetcher->get($page);
            if ($body !== null) {
                $found = array_merge($found, LinkExtractor::icalLinks($body, $page));
            }
        }

        return $found;
    }

    /**
     * @return array<int, string> Absolute iCal/webcal URLs referenced in the HTML.
     */
    private function icalLinks(string $html, string $base): array
    {
        return LinkExtractor::icalLinks($html, $base);
    }
}
