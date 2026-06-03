<?php

namespace App\Console\Commands;

use App\Models\Organizer;
use App\Services\ImpressumImportService;
use Illuminate\Console\Command;

class EnrichOrganizersFromImpressum extends Command
{
    protected $signature = 'organizers:enrich {file : Path to a JSON array of scanned Stammdaten keyed by organizer slug}';

    protected $description = 'Apply Impressum-scanned Stammdaten: fill organizer master data, create the primary venue, download the logo.';

    public function handle(ImpressumImportService $service): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($file), true) ?: [];
        $applied = 0;
        $rejected = 0;

        foreach ($rows as $row) {
            $slug = $row['slug'] ?? null;
            $organizer = $slug ? Organizer::where('slug', $slug)->first() : null;

            if ($organizer === null) {
                $this->warn('skip (unknown slug): '.json_encode($slug));

                continue;
            }

            if (($row['reachable'] ?? true) === false) {
                $service->reject($organizer);
                $rejected++;
                $this->line("rejected (unreachable): {$organizer->slug}");

                continue;
            }

            $service->apply($organizer, $row);

            if (! empty($row['logo_url'])) {
                $service->storeLogoFromUrl($organizer, $row['logo_url']);
            }

            $applied++;
            $this->line("enriched: {$organizer->slug}");
        }

        $this->info("Done. Enriched {$applied}, rejected {$rejected} organizer(s).");

        return self::SUCCESS;
    }
}
