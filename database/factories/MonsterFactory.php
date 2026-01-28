<?php

namespace Database\Factories;

use App\Models\Map;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Monster>
 */
class MonsterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'map_id' => Map::factory(),
            'name' => $this->faker->userName(),
            'hp' => 100,
            'min_dmg' => 5,
            'max_dmg' => 10,
            'speed' => 1.0,
            'base_exp' => 10,
            'base_gold' => 10,
            'element' => 'neutral', // Adjust based on your Enum or String logic
        ];
    }
}
