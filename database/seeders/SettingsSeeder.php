<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds application settings with sensible defaults. Idempotent — an existing
 * value (e.g. toggled in the admin "Allgemein" page) is never overwritten.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Public by default; toggle in admin → Allgemein → "Nur mit Login zu nutzen".
            'login_required' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (! Setting::query()->where('key', $key)->exists()) {
                Setting::put($key, $value);
            }
        }
    }
}
