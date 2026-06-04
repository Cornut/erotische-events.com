<?php

namespace App\Console\Commands;

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Scraping\EventScraperService;
use Illuminate\Console\Command;

class ScrapeEvents extends Command
{
    protected $signature = 'events:scrape {--organizer= : Slug or id of a single organizer to scrape}';

    protected $description = 'Scrape and import events for non-rejected organizers (or one).';

    public function handle(EventScraperService $scraper): int
    {
        $query = Organizer::query()->where('verification_status', '!=', OrganizerVerificationStatus::Rejected->value);

        if ($option = $this->option('organizer')) {
            $query->where(fn ($q) => $q->where('slug', $option)->orWhere('id', $option));
        }

        $organizers = $query->get();
        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($organizers as $organizer) {
            try {
                $r = $scraper->scrape($organizer);
                $totalCreated += $r['created'];
                $totalUpdated += $r['updated'];
                $this->line("{$organizer->slug}: +{$r['created']} new, {$r['updated']} updated");
            } catch (\Throwable $e) {
                $this->warn("{$organizer->slug}: failed — {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Done. {$organizers->count()} organizer(s), {$totalCreated} created, {$totalUpdated} updated.");

        return self::SUCCESS;
    }
}
