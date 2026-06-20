<?php

namespace App\Console\Commands;

use App\Jobs\FetchEventLinkTitle;
use App\Models\Event;
use App\Scraping\HttpPageFetcher;
use Illuminate\Console\Command;

class FetchEventLinkTitles extends Command
{
    protected $signature = 'events:fetch-link-titles
        {--all : Refetch even events that already have a title}
        {--queue : Dispatch one queued job per event instead of fetching synchronously}';

    protected $description = 'Fetch the <title> of each event\'s booking_url target page and store it in booking_title.';

    public function handle(HttpPageFetcher $fetcher): int
    {
        $query = Event::query()
            ->whereNotNull('booking_url')
            ->where('booking_url', '!=', '');

        if (! $this->option('all')) {
            $query->where(fn ($q) => $q->whereNull('booking_title')->orWhere('booking_title', ''));
        }

        $events = $query->get();

        if ($this->option('queue')) {
            $events->each(fn (Event $event) => FetchEventLinkTitle::dispatch($event->id));
            $this->info("Dispatched {$events->count()} title job(s) to the queue.");

            return self::SUCCESS;
        }

        $done = 0;
        foreach ($events as $event) {
            $title = FetchEventLinkTitle::extractTitle($fetcher, $event->booking_url);

            if ($title !== null) {
                $event->forceFill(['booking_title' => $title])->save();
                $done++;
                $this->line("ok: {$title}");
            } else {
                $this->warn("skip: {$event->booking_url}");
            }
        }

        $this->info("Done. Titles fetched for {$done}/{$events->count()} event(s).");

        return self::SUCCESS;
    }
}
