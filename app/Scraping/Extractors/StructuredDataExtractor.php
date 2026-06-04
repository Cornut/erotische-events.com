<?php

namespace App\Scraping\Extractors;

use App\Scraping\ScrapedEvent;

class StructuredDataExtractor implements EventExtractor
{
    public function extract(string $html, string $pageUrl): array
    {
        $events = [];

        if (! preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $m)) {
            return [];
        }

        foreach ($m[1] as $block) {
            $data = json_decode(trim($block), true);
            if (! is_array($data)) {
                continue;
            }
            foreach ($this->eventNodes($data) as $node) {
                $event = $this->toEvent($node, $pageUrl);
                if ($event !== null) {
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function eventNodes(array $data): array
    {
        // Unwrap @graph and arrays of nodes.
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $data = $data['@graph'];
        }
        $nodes = array_is_list($data) ? $data : [$data];

        return array_values(array_filter($nodes, function ($n) {
            if (! is_array($n) || ! isset($n['@type'])) {
                return false;
            }
            $type = is_array($n['@type']) ? implode(',', $n['@type']) : (string) $n['@type'];

            return str_contains($type, 'Event');
        }));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function toEvent(array $node, string $pageUrl): ?ScrapedEvent
    {
        $offer = $node['offers'] ?? null;
        if (isset($offer[0])) {
            $offer = $offer[0];
        }
        $prices = [];
        if (is_array($offer) && isset($offer['price'])) {
            $prices[] = [
                'amount' => (float) $offer['price'],
                'currency' => strtoupper((string) ($offer['priceCurrency'] ?? 'EUR')),
            ];
        }

        $city = $node['location']['address']['addressLocality'] ?? null;

        return ScrapedEvent::fromArray([
            'title' => $node['name'] ?? null,
            'start_date' => $node['startDate'] ?? null,
            'end_date' => $node['endDate'] ?? null,
            'source_url' => $node['url'] ?? $pageUrl,
            'city' => $city,
            'description' => $node['description'] ?? null,
            'image_url' => is_array($node['image'] ?? null) ? ($node['image'][0] ?? null) : ($node['image'] ?? null),
            'prices' => $prices,
        ]);
    }
}
