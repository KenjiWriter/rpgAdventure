<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'class' => \App\Enums\CharacterClass::WARRIOR,
            'level' => 1,
            'experience' => 0,
            'gold' => 100,
            'stat_points' => 0,
            'current_map_id' => null,
        ];
    }
}
