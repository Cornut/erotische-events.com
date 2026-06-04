<?php

namespace App\Scraping\Extractors;

use App\Scraping\ScrapedEvent;

interface EventExtractor
{
    /**
     * @return array<int, ScrapedEvent>
     */
    public function extract(string $html, string $pageUrl): array;
}
