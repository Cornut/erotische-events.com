<?php

namespace App\Console\Commands;

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Scraping\UrlDiscoveryService;
use Illuminate\Console\Command;

class DiscoverEventUrls extends Command
{
    protected $signature = 'organizers:discover-urls {--organizer= : Slug or id of a single organizer} {--limit= : Process at most N organizers}';

    protected $description = 'Use AI to discover each organizer\'s event-listing / iCal URLs, validate them (JSON-LD/iCal), and store the working ones in scrape_urls.';

    public function handle(UrlDiscoveryService $discovery): int
    {
        $query = Organizer::query()
            ->where('verification_status', '!=', OrganizerVerificationStatus::Rejected->value)
            ->orderBy('id');

        if ($option = $this->option('organizer')) {
            $query->where(fn ($q) => $q->where('slug', $option)->orWhere('id', $option));
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $organizers = $query->get();
        $withUrls = 0;

        foreach ($organizers as $organizer) {
            try {
                $urls = $discovery->discover($organizer);
                if ($urls !== []) {
                    $organizer->update(['scrape_urls' => implode("\n", $urls)]);
                    $withUrls++;
                }
                $this->line("{$organizer->slug}: ".count($urls).' url(s)');
            } catch (\Throwable $e) {
                $this->warn("{$organizer->slug}: failed — {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Done. {$organizers->count()} organizer(s) processed, {$withUrls} got scrape_urls.");

        return self::SUCCESS;
    }
}
