<?php

namespace App\Scraping;

interface PageFetcher
{
    public function get(string $url): ?string;
}
