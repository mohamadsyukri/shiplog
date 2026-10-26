<?php

use App\Enums\ImportSource;
use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

it('keeps one card per lane per day', function (): void {
    $lane = Lane::factory()->create();
    Entry::factory()->for($lane)->create(['date' => '2026-10-26']);

    expect(fn () => Entry::factory()->for($lane)->create(['date' => '2026-10-26']))
        ->toThrow(UniqueConstraintViolationException::class);

    Entry::factory()->for(Lane::factory())->create(['date' => '2026-10-26']);
    Entry::factory()->for($lane)->create(['date' => '2026-10-27']);
    expect(Entry::query()->count())->toBe(3);
});

it('keeps each lane key unique', function (): void {
    Lane::factory()->create(['key' => 'manis']);

    expect(fn () => Lane::factory()->create(['key' => 'manis']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('refuses to delete a lane that still has cards', function (): void {
    $lane = Lane::factory()->create();
    Entry::factory()->for($lane)->create();

    expect(fn () => $lane->delete())->toThrow(QueryException::class)
        ->and(Lane::query()->count())->toBe(1);
});

it('reads a card back with its day as Y-m-d and its sources as a list', function (): void {
    $sources = [
        ['type' => 'commit', 'ref' => '51a4d1e', 'label' => 'Setup (Shiplog): rename the template', 'url' => null],
        ['type' => 'artifact', 'ref' => 'lane-board', 'label' => 'Lane Board', 'url' => 'https://claude.ai/artifact/lane-board'],
    ];
    $entry = Entry::factory()->create(['date' => '2026-10-26', 'sources' => $sources])->fresh();

    expect($entry->date->toDateString())->toBe('2026-10-26')
        ->and($entry->toArray()['date'])->toBe('2026-10-26')
        ->and($entry->sources)->toBe($sources);
});

it('records which import wrote a card and who uploaded the file', function (): void {
    $user = User::factory()->create();
    $upload = Import::factory()->for($user)->create(['source' => ImportSource::Upload]);
    $entry = Entry::factory()->for($upload)->create();
    $command = Import::factory()->create()->fresh();

    expect($entry->import->is($upload))->toBeTrue()
        ->and($upload->fresh()->source)->toBe(ImportSource::Upload)
        ->and($upload->user->is($user))->toBeTrue()
        ->and($upload->entries()->sole()->is($entry))->toBeTrue()
        ->and($command->format)->toBe('shiplog/v1')
        ->and($command->source)->toBe(ImportSource::Command)
        ->and($command->user_id)->toBeNull();
});
