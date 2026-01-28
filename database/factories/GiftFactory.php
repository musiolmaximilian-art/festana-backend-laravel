<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Gift;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftFactory extends Factory
{
    protected $model = Gift::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(8),
            'price_cents' => $this->faker->optional()->numberBetween(1000, 50000),
            'currency' => 'EUR',
            'is_visible' => true,
            'sort_order' => 0,
            'is_cash_gift' => false,
            'image_url' => $this->faker->optional()->imageUrl(),
        ];
    }

    public function hidden(): self
    {
        return $this->state([
            'is_visible' => false,
        ]);
    }
}
