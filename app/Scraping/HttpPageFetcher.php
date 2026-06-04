<?php

namespace App\Scraping;

use Illuminate\Support\Facades\Http;
use Throwable;

class HttpPageFetcher implements PageFetcher
{
    public function get(string $url): ?string
    {
        try {
            $response = Http::timeout((int) config('scraping.timeout', 20))
                ->withHeaders(['User-Agent' => 'ErotischeEventsBot/1.0 (+https://erotische-events.com)'])
                ->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
