<?php

namespace Database\Factories;

use App\Enums\OrganizerVerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_user_id' => User::factory(),
            'company_name' => $name,
            'contact_name' => fake()->name(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'social_links' => [],
            'description' => fake()->paragraph(),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'verification_status' => OrganizerVerificationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['verification_status' => OrganizerVerificationStatus::Approved]);
    }
}
