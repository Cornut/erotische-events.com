<?php

namespace App\Scraping;

class ScrapedEvent
{
    /**
     * @param  array<int, array{amount: float, currency: string}>  $prices
     * @param  array<int, string>  $languages
     */
    public function __construct(
        public readonly string $title,
        public readonly string $startDate,
        public readonly ?string $endDate,
        public readonly string $sourceUrl,
        public readonly string $bookingUrl,
        public readonly ?string $city = null,
        public readonly ?string $description = null,
        public readonly ?string $imageUrl = null,
        public readonly array $prices = [],
        public readonly array $languages = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $title = trim((string) ($data['title'] ?? ''));
        $start = trim((string) ($data['start_date'] ?? ''));
        $source = trim((string) ($data['source_url'] ?? ''));

        if ($title === '' || $start === '') {
            return null;
        }

        return new self(
            title: $title,
            startDate: $start,
            endDate: ($data['end_date'] ?? null) ? (string) $data['end_date'] : null,
            sourceUrl: $source,
            bookingUrl: trim((string) ($data['booking_url'] ?? $source)),
            city: isset($data['city']) ? (string) $data['city'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            imageUrl: isset($data['image_url']) ? (string) $data['image_url'] : null,
            prices: array_values($data['prices'] ?? []),
            languages: array_values($data['languages'] ?? []),
        );
    }
}
