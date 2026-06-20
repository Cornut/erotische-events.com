<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Idempotent — safe to re-run on staging.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,   // admin account (env-driven credentials)
            CategorySeeder::class,    // main + sub categories
            OrganizerSeeder::class,   // curated organizers + venues + logos
            SettingsSeeder::class,    // app settings defaults
        ]);
    }
}
