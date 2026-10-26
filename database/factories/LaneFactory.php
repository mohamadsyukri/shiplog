<?php

namespace Database\Factories;

use App\Models\Lane;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lane>
 */
class LaneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => str($name)->slug()->toString(),
            'name' => ucwords($name),
            'color' => fake()->hexColor(),
            'sort' => 0,
        ];
    }
}
