<?php

use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use App\Models\User;
use App\Services\Imports\ImportService;
use Database\Seeders\DemoSeeder;

it('refuses to run unless demo mode is on, so a real diary never gets invented cards', function (): void {
    expect(fn () => $this->seed(DemoSeeder::class))->toThrow(RuntimeException::class, 'DEMO_MODE=true');

    nothingWritten();
});

it('seeds the same invented board every time it runs', function (): void {
    config(['shiplog.demo' => true]);

    $this->seed(DemoSeeder::class);
    $first = Entry::query()->orderBy('date')->orderBy('lane_id')->get(['date', 'lane_id', 'summary', 'sources'])->toArray();
    $this->seed(DemoSeeder::class);
    $second = Entry::query()->orderBy('date')->orderBy('lane_id')->get(['date', 'lane_id', 'summary', 'sources'])->toArray();

    expect($second)->toBe($first)
        ->and(Lane::query()->orderBy('sort')->pluck('name')->all())->toBe(['Client portal', 'Mobile app', 'Side project', 'Other'])
        ->and(count($first))->toBeGreaterThan(90)
        ->and(Entry::query()->min('date'))->toBeGreaterThanOrEqual('2026-07-01')
        ->and(Entry::query()->max('date'))->toBe('2026-09-28');

    $import = Import::query()->sole();
    expect($import->file_name)->toBe('demo.json')
        ->and($import->range_to->toDateString())->toBe('2026-09-28')
        ->and($import->entries_created)->toBe(count($first));
});

it('writes only cards the importer would accept', function (): void {
    config(['shiplog.demo' => true]);
    $this->seed(DemoSeeder::class);

    $file = [
        'format' => ImportService::FORMAT,
        'range' => ['from' => '2026-07-01', 'to' => '2026-09-28'],
        'lanes' => Lane::query()->get()->map(fn (Lane $lane): array => ['key' => $lane->key, 'name' => $lane->name, 'color' => $lane->color, 'order' => $lane->sort])->all(),
        'entries' => Entry::query()->with('lane')->get()->map(fn (Entry $entry): array => [
            'date' => $entry->date->toDateString(),
            'lane' => $entry->lane->key,
            'summary' => $entry->summary,
            'sources' => $entry->sources,
        ])->all(),
    ];

    expect(app(ImportService::class)->validate(json_encode($file, JSON_THROW_ON_ERROR)))->toBe([]);
});

it('shows no demo banner outside demo mode', function (): void {
    $this->actingAs(User::factory()->create())->get(route('board.index'))->assertOk()->assertDontSee('made-up data');
});
