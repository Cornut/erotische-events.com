<?php

namespace App\Scraping;

/**
 * Pure HTML link helpers shared by the scraper and the URL-discovery service.
 * No network access — operates on already-fetched HTML.
 */
class LinkExtractor
{
    /**
     * @return array<int, string> Absolute same-domain page URLs linked from the HTML.
     */
    public static function sameDomainLinks(string $html, string $base): array
    {
        if (! preg_match_all('/href\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
            return [];
        }

        $host = parse_url($base, PHP_URL_HOST);
        $links = [];
        foreach ($m[1] as $href) {
            if (str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $abs = self::absoluteUrl($href, $base);
            if ($abs !== null && parse_url($abs, PHP_URL_HOST) === $host && ! self::isAsset($abs)) {
                $links[] = $abs;
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * Static assets and machine endpoints worth skipping when crawling for content.
     */
    public static function isAsset(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        if (preg_match('/\.(jpe?g|png|gif|svg|webp|avif|ico|css|js|mjs|map|woff2?|ttf|eot|otf|mp4|webm|mov|mp3|wav|pdf|zip|gz|rss|xml)$/', $path)) {
            return true;
        }

        return (bool) preg_match('#/(wp-content|wp-includes|wp-json|feed|comments/feed)(/|$)#', $path);
    }

    /**
     * @return array<int, string> Absolute iCal/webcal URLs referenced in the HTML.
     */
    public static function icalLinks(string $html, string $base): array
    {
        if (! preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
            return [];
        }

        $links = [];
        foreach ($m[1] as $href) {
            if (preg_match('/\.ics(\?|#|$)|webcal:|\/ics\//i', $href)) {
                $abs = self::absoluteUrl($href, $base);
                if ($abs !== null) {
                    $links[] = $abs;
                }
            }
        }

        return array_values(array_unique($links));
    }

    public static function absoluteUrl(string $href, string $base): ?string
    {
        $href = trim($href);
        if ($href === '') {
            return null;
        }
        if (str_starts_with($href, 'webcal:')) {
            return 'https:'.substr($href, strlen('webcal:'));
        }
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST);
        if ($host === null) {
            return null;
        }
        $origin = "{$scheme}://{$host}";

        if (str_starts_with($href, '//')) {
            return "{$scheme}:{$href}";
        }
        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }

        $path = (string) parse_url($base, PHP_URL_PATH);
        $dir = rtrim(substr($path, 0, (int) strrpos($path, '/') + 1), '/');

        return "{$origin}{$dir}/{$href}";
    }
}
