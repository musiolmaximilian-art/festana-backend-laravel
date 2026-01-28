<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'website_name' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence(3),
            'wedding_date' => $this->faker->optional()->date(),
            'timezone' => 'Europe/Berlin',
            'public_settings' => ['is_public' => false],
        ];
    }

    public function public(): self
    {
        return $this->state([
            'public_settings' => ['is_public' => true],
        ]);
    }
}
