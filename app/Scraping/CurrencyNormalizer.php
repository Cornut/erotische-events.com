<?php

namespace App\Scraping;

class CurrencyNormalizer
{
    /** @param array<string, float> $rates */
    public function __construct(private readonly array $rates) {}

    public static function fromConfig(): self
    {
        return new self(config('scraping.fx', ['EUR' => 1.0]));
    }

    public function toEur(float $amount, ?string $currency): float
    {
        $code = strtoupper((string) ($currency ?: 'EUR'));
        $rate = $this->rates[$code] ?? 1.0;

        return round($amount * $rate, 2);
    }
}
