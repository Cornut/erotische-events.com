<?php

namespace App\Scraping\Extractors;

use App\Scraping\Llm\LlmClient;
use App\Scraping\ScrapedEvent;

class LlmEventExtractor implements EventExtractor
{
    public function __construct(private readonly LlmClient $client) {}

    public function extract(string $html, string $pageUrl): array
    {
        $events = [];
        foreach ($this->client->extractEvents($html, $pageUrl) as $row) {
            $event = ScrapedEvent::fromArray($row);
            if ($event !== null) {
                $events[] = $event;
            }
        }

        return $events;
    }
}
