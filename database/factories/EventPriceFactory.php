<?php

namespace Database\Factories;

use App\Enums\EventPriceType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'type' => EventPriceType::Regular,
            'amount' => fake()->randomFloat(2, 50, 500),
            'currency' => 'EUR',
        ];
    }
}
