<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\Lane;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->toDateString(),
            'lane_id' => Lane::factory(),
            'summary' => fake()->sentences(3, true),
            'sources' => [
                ['type' => 'commit', 'ref' => fake()->regexify('[0-9a-f]{7}'), 'label' => fake()->sentence(6), 'url' => null],
            ],
            'import_id' => null,
        ];
    }
}
