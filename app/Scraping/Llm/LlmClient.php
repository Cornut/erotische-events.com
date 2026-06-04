<?php

namespace App\Scraping\Llm;

interface LlmClient
{
    /**
     * @return array<int, array<string, mixed>> Raw event arrays (title, start_date, source_url, ...).
     */
    public function extractEvents(string $content, string $pageUrl): array;

    /**
     * Identify, from a page's HTML, absolute URLs that list multiple events/seminars
     * or are iCal (.ics) calendar feeds (same domain).
     *
     * @return array<int, string>
     */
    public function findEventUrls(string $content, string $baseUrl): array;
}
