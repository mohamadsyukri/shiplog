<?php

use App\Services\Imports\ImportService;

/*
 * The skill ships an example config (skill/shiplog-update/config.example.json). Its lanes are
 * copied into every file the skill writes, so they must pass the importer's own rules.
 */
function skillConfigExample(): array
{
    return json_decode((string) file_get_contents(base_path('skill/shiplog-update/config.example.json')), true, 512, JSON_THROW_ON_ERROR);
}

it('gives the skill lanes the importer accepts', function (): void {
    $file = [
        'format' => ImportService::FORMAT,
        'range' => ['from' => '2026-10-20', 'to' => '2026-10-20'],
        'lanes' => array_map(
            fn (array $lane): array => array_intersect_key($lane, array_flip(['key', 'name', 'color', 'order'])),
            skillConfigExample()['lanes'],
        ),
        'entries' => [],
    ];

    expect(app(ImportService::class)->validate(json_encode($file, JSON_THROW_ON_ERROR)))->toBe([]);
});

it('names a real timezone, a catch-all lane among its lanes, and an import the skill can run', function (): void {
    $config = skillConfigExample();

    expect(in_array($config['timezone'], timezone_identifiers_list(), true))->toBeTrue()
        ->and($config['git_emails'])->not->toBeEmpty()
        ->and(array_column($config['lanes'], 'key'))->toContain($config['catch_all_lane'])
        ->and($config['import']['mode'])->toBeIn(['command', 'upload'])
        ->and($config['import']['command'])->toContain('{name}')
        ->and(array_map(fn (array $lane): bool => is_array($lane['repos']), $config['lanes']))->each->toBeTrue();
});
