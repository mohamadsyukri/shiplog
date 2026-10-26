<?php

use App\Enums\ImportSource;
use App\Models\Entry;
use App\Models\Import;

beforeEach(function (): void {
    $this->path = sys_get_temp_dir().'/shiplog-2026-10-20_to_2026-10-21.json';
    file_put_contents($this->path, shiplogFile());
});

afterEach(function (): void {
    @unlink($this->path);
});

it('imports a file and prints the range, lanes and cards', function (): void {
    $this->artisan('shiplog:import', ['file' => $this->path])
        ->expectsOutputToContain('Imported shiplog-2026-10-20_to_2026-10-21.json.')
        ->expectsTable(['Range', 'Lanes', 'Cards created', 'Cards replaced'], [['2026-10-20 to 2026-10-21', 3, 2, 0]])
        ->assertExitCode(0);

    $import = Import::query()->sole();
    expect($import->source)->toBe(ImportSource::Command)
        ->and($import->file_name)->toBe('shiplog-2026-10-20_to_2026-10-21.json')
        ->and($import->user_id)->toBeNull()
        ->and(Entry::query()->count())->toBe(2);
});

it('counts the cards of a second import as replaced', function (): void {
    $this->artisan('shiplog:import', ['file' => $this->path])->assertExitCode(0);

    $this->artisan('shiplog:import', ['file' => $this->path])
        ->expectsTable(['Range', 'Lanes', 'Cards created', 'Cards replaced'], [['2026-10-20 to 2026-10-21', 3, 0, 2]])
        ->assertExitCode(0);
});

it('lists every problem and exits 1 when the file is refused', function (): void {
    file_put_contents($this->path, shiplogFile(['lanes.0.color' => 'blue', 'entries.1.summary' => '']));

    $this->artisan('shiplog:import', ['file' => $this->path])
        ->expectsOutputToContain('The file was refused: 2 problems.')
        ->expectsOutputToContain('lanes[0] color "blue" is not written #rrggbb.')
        ->expectsOutputToContain('entries[1] 2026-10-21 lane "side-project" summary is empty.')
        ->assertExitCode(1);

    nothingWritten();
});

it('only checks a valid file with --dry-run', function (): void {
    $this->artisan('shiplog:import', ['file' => $this->path, '--dry-run' => true])
        ->expectsOutputToContain('The file is valid: 3 lanes and 2 cards from 2026-10-20 to 2026-10-21. Nothing was written.')
        ->assertExitCode(0);

    nothingWritten();
});

it('lists the problems and exits 1 when --dry-run finds any', function (): void {
    file_put_contents($this->path, shiplogFile(['entries.0.lane' => 'mobile-app']));

    $this->artisan('shiplog:import', ['file' => $this->path, '--dry-run' => true])
        ->expectsOutputToContain('The file was refused: 1 problem.')
        ->expectsOutputToContain('entries[0] 2026-10-20 lane "mobile-app" is not in lanes.')
        ->assertExitCode(1);

    nothingWritten();
});

it('exits 1 when there is no file at the path', function (): void {
    $missing = sys_get_temp_dir().'/no-such-shiplog-file.json';

    $this->artisan('shiplog:import', ['file' => $missing])
        ->expectsOutputToContain("No readable file at {$missing}.")
        ->assertExitCode(1);

    nothingWritten();
});
