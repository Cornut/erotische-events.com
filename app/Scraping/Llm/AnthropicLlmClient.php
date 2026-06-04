<?php

namespace App\Scraping\Llm;

use Illuminate\Support\Facades\Http;
use Throwable;

class AnthropicLlmClient implements LlmClient
{
    public function extractEvents(string $content, string $pageUrl): array
    {
        $prompt = 'Extract all events from this page as a JSON array. Each item: '
            .'{title, start_date (Y-m-d H:i), end_date|null, source_url (absolute), city|null, '
            .'description|null, image_url|null, prices:[{amount:number, currency:ISO}], '
            .'teachers:[names of the teachers/instructors/leaders presenting the event]}. '
            ."Page URL: {$pageUrl}. Return ONLY the JSON array, no prose.\n\n"
            .mb_substr(strip_tags($content), 0, 60000);

        $decoded = $this->ask($prompt);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    public function findEventUrls(string $content, string $baseUrl): array
    {
        // Keep the raw HTML (don't strip tags) so the model can see the anchor hrefs.
        $prompt = 'Below is the HTML of a website page. Return a JSON array of ABSOLUTE URLs '
            .'(same domain as the base URL) that either (a) list multiple events/seminars/dates, '
            .'or (b) are iCal/.ics calendar feeds. Resolve relative links to absolute using the base URL. '
            ."Exclude blog/article/social URLs. Base URL: {$baseUrl}. Return ONLY the JSON array of strings.\n\n"
            .mb_substr($content, 0, 60000);

        $decoded = $this->ask($prompt);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($u) => is_string($u) ? trim($u) : '', $decoded),
            fn (string $u) => str_starts_with($u, 'http'),
        ));
    }

    /**
     * Send a single user prompt to the Anthropic Messages API and return the decoded
     * JSON from the reply, or null on missing key / error / unparseable output.
     */
    private function ask(string $prompt): mixed
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 4096,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (! $response->successful()) {
                return null;
            }

            $text = trim(preg_replace('/^```(?:json)?|```$/m', '', (string) $response->json('content.0.text', '')));

            return json_decode($text, true);
        } catch (Throwable) {
            return null;
        }
    }
}
