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
        $seed = $organizer->events_url ?: $organizer->website;
        if (empty($seed)) {
            return [];
        }

        $html = $this->fetcher->get($seed);
        if ($html === null && $organizer->website && $organizer->website !== $seed) {
            $seed = $organizer->website;
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

        return array_values(array_unique($valid));
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

        foreach (array_slice($this->sameDomainLinks($html, $seed), 0, $max) as $page) {
            $body = $this->fetcher->get($page);
            if ($body !== null) {
                $found = array_merge($found, $this->icalLinks($body, $page));
            }
        }

        return $found;
    }

    /**
     * @return array<int, string> Absolute iCal/webcal URLs referenced in the HTML.
     */
    private function icalLinks(string $html, string $base): array
    {
        if (! preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
            return [];
        }

        $links = [];
        foreach ($m[1] as $href) {
            if (preg_match('/\.ics(\?|#|$)|webcal:|\/ics\//i', $href)) {
                $abs = $this->absoluteUrl($href, $base);
                if ($abs !== null) {
                    $links[] = $abs;
                }
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * @return array<int, string> Absolute same-domain page URLs linked from the HTML.
     */
    private function sameDomainLinks(string $html, string $base): array
    {
        if (! preg_match_all('/href\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
            return [];
        }

        $host = parse_url($base, PHP_URL_HOST);
        $links = [];
        foreach ($m[1] as $href) {
            if (str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $abs = $this->absoluteUrl($href, $base);
            if ($abs !== null && parse_url($abs, PHP_URL_HOST) === $host) {
                $links[] = $abs;
            }
        }

        return array_values(array_unique($links));
    }

    private function absoluteUrl(string $href, string $base): ?string
    {
        $href = trim($href);
        if ($href === '') {
            return null;
        }
        if (str_starts_with($href, 'webcal:')) {
            return 'https:'.substr($href, strlen('webcal:'));
        }
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST);
        if ($host === null) {
            return null;
        }
        $origin = "{$scheme}://{$host}";

        if (str_starts_with($href, '//')) {
            return "{$scheme}:{$href}";
        }
        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }

        $path = (string) parse_url($base, PHP_URL_PATH);
        $dir = rtrim(substr($path, 0, (int) strrpos($path, '/') + 1), '/');

        return "{$origin}{$dir}/{$href}";
    }
}
