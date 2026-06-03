<?php

namespace Database\Seeders;

use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizerSeeder extends Seeder
{
    /**
     * Seed organizers from the curated list (database/seeders/data/organizers.json).
     * All are assigned to the admin account as owner and set to Approved so the
     * curated catalog is publicly visible. Stammdaten from each Impressum
     * (contact_name, email, phone, address-as-venue) are a separate later step.
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
    }
}
