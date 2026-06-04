<?php

namespace App\Scraping\Llm;

interface LlmClient
{
    /**
     * @return array<int, array<string, mixed>> Raw event arrays (title, start_date, source_url, ...).
     */
    public function extractEvents(string $content, string $pageUrl): array;
}
