<?php

namespace Database\Seeders;

use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;
use App\Services\ImpressumImportService;
use Illuminate\Database\Seeder;

class OrganizerSeeder extends Seeder
{
    /**
     * Seed organizers from the curated list (database/seeders/data/organizers.json),
     * owned by the admin and set to Approved so the catalog is publicly visible,
     * then enrich them with the scanned Impressum Stammdaten from
     * database/seeders/data/impressum.json (master data + primary venue + logo).
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@erotische-events.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => UserRole::Admin,
                'locale' => 'de',
            ],
        );

        $path = database_path('seeders/data/organizers.json');
        $rows = json_decode((string) file_get_contents($path), true) ?: [];

        foreach ($rows as $row) {
            Organizer::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'owner_user_id' => $admin->id,
                    'company_name' => $row['company_name'],
                    'website' => $row['website'],
                    'description' => $row['description'],
                    'social_links' => [],
                    'verification_status' => OrganizerVerificationStatus::Approved,
                ],
            );
        }

        $this->applyImpressumData();
    }

    /**
     * Apply scanned Impressum Stammdaten (master data + primary venue, and the
     * logo download outside the test environment to keep the suite network-free).
     */
    private function applyImpressumData(): void
    {
        $path = database_path('seeders/data/impressum.json');
        if (! is_file($path)) {
            return;
        }

        $rows = json_decode((string) file_get_contents($path), true) ?: [];
        $service = app(ImpressumImportService::class);
        $downloadLogos = ! app()->runningUnitTests();

        foreach ($rows as $row) {
            $slug = $row['slug'] ?? null;
            $organizer = $slug ? Organizer::where('slug', $slug)->first() : null;

            if ($organizer === null) {
                continue;
            }

            if (($row['reachable'] ?? true) === false) {
                $service->reject($organizer);

                continue;
            }

            $service->apply($organizer, $row);

            if ($downloadLogos && ! empty($row['logo_url'])) {
                $service->storeLogoFromUrl($organizer, $row['logo_url']);
            }
        }
    }
}
