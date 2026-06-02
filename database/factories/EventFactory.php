<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $start = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'organizer_id' => Organizer::factory(),
            'venue_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'short_description' => fake()->sentence(),
            'long_description' => fake()->paragraphs(3, true),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+2 days'),
            'status' => EventStatus::Draft,
            'audience' => ['everyone'],
            'languages' => ['de'],
            'accommodation' => 'none',
            'currency' => 'EUR',
            'booking_url' => fake()->url(),
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => EventStatus::Published]);
    }
}
