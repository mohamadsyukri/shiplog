<?php

use App\Enums\ImportSource;
use App\Exceptions\Imports\ImportRejected;
use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use App\Models\User;
use App\Services\Imports\ImportResult;
use App\Services\Imports\ImportService;

beforeEach(function (): void {
    $this->importer = app(ImportService::class);
});

it('creates the lanes and cards a valid file lists', function (): void {
    $user = User::factory()->create();

    $result = $this->importer->import(shiplogFile(), ImportSource::Upload, 'shiplog-2026-10-20_to_2026-10-21.json', $user);

    expect($result)->toEqual(new ImportResult('2026-10-20', '2026-10-21', 3, 2, 0))
        ->and(Lane::query()->orderBy('sort')->get(['key', 'name', 'color', 'sort'])->toArray())->toBe([
            ['key' => 'client-portal', 'name' => 'Client portal', 'color' => '#2563eb', 'sort' => 1],
            ['key' => 'side-project', 'name' => 'Side project', 'color' => '#16a34a', 'sort' => 2],
            ['key' => 'other', 'name' => 'Other', 'color' => '#6b7280', 'sort' => 3],
        ]);

    $import = Import::query()->sole();
    expect($import->file_name)->toBe('shiplog-2026-10-20_to_2026-10-21.json')
        ->and($import->range_from->toDateString())->toBe('2026-10-20')
        ->and($import->range_to->toDateString())->toBe('2026-10-21')
        ->and($import->format)->toBe('shiplog/v1')
        ->and($import->source)->toBe(ImportSource::Upload)
        ->and($import->only(['lanes_upserted', 'entries_created', 'entries_replaced', 'user_id']))
        ->toBe(['lanes_upserted' => 3, 'entries_created' => 2, 'entries_replaced' => 0, 'user_id' => $user->id]);

    $card = Entry::query()->with('lane')->where('date', '2026-10-20')->sole();
    expect($card->lane->key)->toBe('client-portal')
        ->and($card->summary)->toStartWith('Fixed the invoice total')
        ->and($card->sources)->toBe(json_decode(shiplogFile(), true)['entries'][0]['sources'])
        ->and($card->import_id)->toBe($import->id);
});

it('gives the same board when the same file is imported twice', function (): void {
    $this->importer->import(shiplogFile(), ImportSource::Command, 'shiplog.json');
    $second = $this->importer->import(shiplogFile(), ImportSource::Command, 'shiplog.json');

    expect($second)->toEqual(new ImportResult('2026-10-20', '2026-10-21', 3, 0, 2))
        ->and(Lane::query()->count())->toBe(3)
        ->and(Entry::query()->count())->toBe(2)
        ->and(Import::query()->count())->toBe(2);
});

it("replaces a day's card and updates lane names and colours on a later import", function (): void {
    $this->importer->import(shiplogFile(), ImportSource::Command, 'first.json');

    $result = $this->importer->import(shiplogFile([
        'lanes.0.name' => 'Customer portal',
        'lanes.0.color' => '#7c3aed',
        'entries.0.summary' => 'Fixed the invoice total and released it.',
    ]), ImportSource::Command, 'second.json');

    $lane = Lane::query()->where('key', 'client-portal')->sole();
    $card = Entry::query()->whereBelongsTo($lane)->sole();
    expect($result->entriesCreated)->toBe(0)
        ->and($result->entriesReplaced)->toBe(2)
        ->and($lane->only(['name', 'color']))->toBe(['name' => 'Customer portal', 'color' => '#7c3aed'])
        ->and($card->summary)->toBe('Fixed the invoice total and released it.')
        ->and($card->import_id)->toBe(Import::query()->where('file_name', 'second.json')->value('id'));
});

it('never deletes cards the file does not mention', function (): void {
    $portal = Lane::factory()->create(['key' => 'client-portal']);
    $other = Lane::factory()->create(['key' => 'other']);
    $dayBefore = Entry::factory()->for($portal)->create(['date' => '2026-10-19', 'summary' => 'The day before the range.']);
    $sameDay = Entry::factory()->for($other)->create(['date' => '2026-10-20', 'summary' => 'A lane the file has no card for.']);

    $this->importer->import(shiplogFile(), ImportSource::Command, 'shiplog.json');

    expect(Entry::query()->count())->toBe(4)
        ->and($dayBefore->fresh()->summary)->toBe('The day before the range.')
        ->and($sameDay->fresh()->summary)->toBe('A lane the file has no card for.');
});

it('refuses the whole file and writes nothing', function (string $json, string $problem): void {
    expect($this->importer->validate($json))->toContain($problem);

    try {
        $this->importer->import($json, ImportSource::Command, 'broken.json');
        $this->fail('The file was imported.');
    } catch (ImportRejected $rejected) {
        expect($rejected->errors())->toContain($problem);
    }

    expect(Lane::query()->count())->toBe(0)
        ->and(Entry::query()->count())->toBe(0)
        ->and(Import::query()->count())->toBe(0);
})->with([
    'broken JSON' => [fn () => '{"format": "shiplog/v1", ', 'The file is not valid JSON (Syntax error).'],
    'not an object' => [fn () => '["shiplog/v1"]', 'The file must hold one JSON object.'],
    'unknown format' => [fn () => shiplogFile(['format' => 'shiplog/v2']), 'format "shiplog/v2" is not supported; this version of Shiplog reads "shiplog/v1".'],
    'range backwards' => [fn () => shiplogFile(['range.from' => '2026-10-22']), 'range.from 2026-10-22 is after range.to 2026-10-21.'],
    'lane key listed twice' => [fn () => shiplogFile(['lanes.2.key' => 'client-portal']), 'lanes[2] key "client-portal" is listed twice.'],
    'capitals in a lane key' => [fn () => shiplogFile(['lanes.2.key' => 'Other']), 'lanes[2] key "Other" must be 1 to 40 lowercase letters, digits or hyphens.'],
    'bad colour' => [fn () => shiplogFile(['lanes.0.color' => 'blue']), 'lanes[0] color "blue" is not written #rrggbb.'],
    'order not a whole number' => [fn () => shiplogFile(['lanes.0.order' => 1.5]), 'lanes[0] order 1.5 must be a whole number from 0 to 65535.'],
    'unknown field' => [fn () => shiplogFile(['entries.0.mood' => 'happy']), 'entries[0] 2026-10-20 lane "client-portal" has an unknown field "mood".'],
    'missing summary' => [fn () => shiplogFile(remove: ['entries.0.summary']), 'entries[0] 2026-10-20 lane "client-portal" is missing summary.'],
    'impossible date' => [fn () => shiplogFile(['entries.0.date' => '2026-02-30']), 'entries[0] 2026-02-30 lane "client-portal" date is not a date written YYYY-MM-DD.'],
    'date outside the range' => [fn () => shiplogFile(['entries.0.date' => '2026-10-22']), 'entries[0] 2026-10-22 lane "client-portal" is outside the range 2026-10-20 to 2026-10-21.'],
    'unknown lane' => [fn () => shiplogFile(['entries.0.lane' => 'mobile-app']), 'entries[0] 2026-10-20 lane "mobile-app" is not in lanes.'],
    'empty summary' => [fn () => shiplogFile(['entries.0.summary' => '   ']), 'entries[0] 2026-10-20 lane "client-portal" summary is empty.'],
    'summary over 600 characters' => [fn () => shiplogFile(['entries.0.summary' => str_repeat('a', 601)]), 'entries[0] 2026-10-20 lane "client-portal" summary is 601 characters; the limit is 600.'],
    'second card for a day and lane' => [
        fn () => shiplogFile(['entries.2' => json_decode(shiplogFile(), true)['entries'][0]]),
        'entries[2] 2026-10-20 lane "client-portal" is a second card for the same day and lane.',
    ],
    'no sources' => [fn () => shiplogFile(['entries.0.sources' => []]), 'entries[0] 2026-10-20 lane "client-portal" has no sources; every card comes from at least one record.'],
    'unknown source type' => [
        fn () => shiplogFile(['entries.0.sources.0.type' => 'meeting']),
        'entries[0] 2026-10-20 lane "client-portal" sources[0] type "meeting" is not one of commit, session, artifact, note.',
    ],
    'script address' => [
        fn () => shiplogFile(['entries.0.sources.0.url' => 'javascript:alert(1)']),
        'entries[0] 2026-10-20 lane "client-portal" sources[0] url must be an http or https address, or null.',
    ],
]);

it('lists every problem in a file, not only the first', function (): void {
    $errors = $this->importer->validate(shiplogFile(['lanes.0.color' => 'blue', 'entries.1.summary' => '']));

    expect($errors)->toBe([
        'lanes[0] color "blue" is not written #rrggbb.',
        'entries[1] 2026-10-21 lane "side-project" summary is empty.',
    ]);
});

it('documents in the README exactly the example file the importer is tested with', function (): void {
    $readme = (string) file_get_contents(base_path('README.md'));

    expect(preg_match('/^## Import file format$.*?^```json\n(.*?)^```$/ms', $readme, $block))->toBe(1)
        ->and(json_decode($block[1], true))->toBe(json_decode((string) file_get_contents(base_path('tests/Fixtures/shiplog-example.json')), true))
        ->and($this->importer->validate($block[1]))->toBe([]);
});
