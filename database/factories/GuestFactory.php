<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->optional()->lastName(),
            'email' => $this->faker->optional()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'tags' => [],
            'has_plus_one' => $this->faker->boolean(20),
            'dietary_restrictions' => $this->faker->optional()->word(),
            'preferred_meal' => $this->faker->optional()->word(),
            'attendance_status' => $this->faker->randomElement(['invited', 'attending', 'declined', 'unknown']),
            'notes' => $this->faker->optional()->sentence(),
            'invitation_token_hash' => $this->faker->optional()->sha256(),
        ];
    }
}
