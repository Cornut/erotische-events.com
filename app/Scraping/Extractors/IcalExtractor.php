<?php

namespace App\Scraping\Extractors;

use App\Scraping\ScrapedEvent;

/**
 * Parses iCalendar (RFC 5545) feeds — VEVENT blocks — into ScrapedEvents.
 * No AI involved. Used for organizers that expose a .ics calendar feed.
 */
class IcalExtractor implements EventExtractor
{
    public function extract(string $content, string $pageUrl): array
    {
        if (! str_contains($content, 'BEGIN:VCALENDAR')) {
            return [];
        }

        // Unfold folded lines (a leading space/tab continues the previous line).
        $content = preg_replace('/\r\n[ \t]|\n[ \t]|\r[ \t]/', '', $content);

        if (! preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', (string) $content, $blocks)) {
            return [];
        }

        $events = [];
        foreach ($blocks[1] as $block) {
            $props = $this->properties($block);

            $start = $this->toDateTime($props['DTSTART'] ?? null);
            $title = $props['SUMMARY'] ?? null;
            if ($start === null || $title === null) {
                continue;
            }

            $source = $props['URL'] ?? ($pageUrl.'#'.($props['UID'] ?? md5($title.$start)));

            $event = ScrapedEvent::fromArray([
                'title' => $this->unescape($title),
                'start_date' => $start,
                'end_date' => $this->toDateTime($props['DTEND'] ?? null),
                'source_url' => $source,
                'booking_url' => $props['URL'] ?? $pageUrl,
                'city' => isset($props['LOCATION']) ? $this->unescape($props['LOCATION']) : null,
                'description' => isset($props['DESCRIPTION']) ? $this->unescape($props['DESCRIPTION']) : null,
            ]);

            if ($event !== null) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * @return array<string, string> Property name (without params) => raw value.
     */
    private function properties(string $block): array
    {
        $props = [];
        foreach (preg_split('/\r\n|\n|\r/', trim($block)) as $line) {
            if (! preg_match('/^([A-Z][A-Z0-9-]*)(;[^:]*)?:(.*)$/', $line, $m)) {
                continue;
            }
            $props[strtoupper($m[1])] ??= $m[3];
        }

        return $props;
    }

    private function toDateTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})(\d{2})(\d{2})(?:T(\d{2})(\d{2})(\d{2}))?/', trim($value), $m)) {
            $hour = $m[4] ?? '00';
            $min = $m[5] ?? '00';

            return "{$m[1]}-{$m[2]}-{$m[3]} {$hour}:{$min}";
        }

        return null;
    }

    private function unescape(string $value): string
    {
        return trim(str_replace(['\\,', '\;', '\\n', '\\N'], [',', ';', "\n", "\n"], $value));
    }
}
