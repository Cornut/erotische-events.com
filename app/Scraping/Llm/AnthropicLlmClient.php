<?php

namespace App\Scraping\Llm;

use Illuminate\Support\Facades\Http;
use Throwable;

class AnthropicLlmClient implements LlmClient
{
    public function extractEvents(string $content, string $pageUrl): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            return [];
        }

        $prompt = 'Extract all events from this page as a JSON array. Each item: '
            .'{title, start_date (Y-m-d H:i), end_date|null, source_url (absolute), city|null, '
            .'description|null, image_url|null, prices:[{amount:number, currency:ISO}], '
            .'teachers:[names of the teachers/instructors/leaders presenting the event]}. '
            ."Page URL: {$pageUrl}. Return ONLY the JSON array, no prose.\n\n"
            .mb_substr(strip_tags($content), 0, 60000);

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
                return [];
            }

            $text = $response->json('content.0.text', '');
            $text = trim(preg_replace('/^```(?:json)?|```$/m', '', (string) $text));
            $decoded = json_decode($text, true);

            return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
        } catch (Throwable) {
            return [];
        }
    }
}
