<?php

namespace Database\Factories;

use App\Enums\ImportSource;
use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_name' => 'shiplog-'.now()->toDateString().'.json',
            'range_from' => now()->subDays(6)->toDateString(),
            'range_to' => now()->toDateString(),
            'format' => 'shiplog/v1',
            'source' => ImportSource::Command,
            'lanes_upserted' => 0,
            'entries_created' => 0,
            'entries_replaced' => 0,
            'user_id' => null,
        ];
    }
}
