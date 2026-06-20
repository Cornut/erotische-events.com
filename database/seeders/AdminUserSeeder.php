<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the admin account used to reach the Filament panel (/admin).
 *
 * Credentials are taken from env so staging can use a real password:
 *   ADMIN_EMAIL, ADMIN_NAME, ADMIN_PASSWORD
 *
 * NOTE: run `db:seed` BEFORE `config:cache` so env() resolves here.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@erotische-events.com');
        $password = (string) env('ADMIN_PASSWORD', 'password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('ADMIN_NAME', 'Admin'),
                'password' => bcrypt($password),
                'role' => UserRole::Admin,
                'locale' => 'de',
            ],
        );

        // Make sure a pre-existing user keeps admin access.
        if ($user->role !== UserRole::Admin) {
            $user->update(['role' => UserRole::Admin]);
        }
    }
}
