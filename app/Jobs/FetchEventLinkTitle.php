<?php

namespace App\Jobs;

use App\Models\Event;
use App\Scraping\HttpPageFetcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fetches the <title> of an event's booking_url target page and stores it in
 * booking_title. Runs on the queue so imports/scrapes stay fast.
 */
class FetchEventLinkTitle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $eventId) {}

    public function handle(HttpPageFetcher $fetcher): void
    {
        $event = Event::find($this->eventId);
        if (! $event || empty($event->booking_url)) {
            return;
        }

        $title = self::extractTitle($fetcher, $event->booking_url);
        if ($title !== null) {
            // saveQuietly: no need to re-index in Scout (title isn't searchable).
            $event->forceFill(['booking_title' => $title])->saveQuietly();
        }
    }

    public static function extractTitle(HttpPageFetcher $fetcher, string $url): ?string
    {
        $html = $fetcher->get($url);
        if ($html === null) {
            return null;
        }

        if (! preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            return null;
        }

        $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');

        return $title !== '' ? mb_substr($title, 0, 255) : null;
    }
}
