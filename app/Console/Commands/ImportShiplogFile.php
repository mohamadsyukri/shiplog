<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ImportSource;
use App\Exceptions\Imports\ImportRejected;
use App\Services\Imports\ImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('shiplog:import {file : Path to a Shiplog JSON file} {--dry-run : Only check the file; write nothing}')]
#[Description('Add or replace the board\'s cards for the days in a Shiplog file; never deletes a card')]
final class ImportShiplogFile extends Command
{
    public function handle(ImportService $importer): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->components->error(__('No readable file at :path.', ['path' => $path]));

            return self::FAILURE;
        }
        $json = (string) file_get_contents($path);

        // The skill runs --dry-run before importing, so it can fix a file while it still knows why
        // each card was written.
        if ($this->option('dry-run')) {
            $errors = $importer->validate($json);

            return $errors === [] ? $this->reportValid($json) : $this->refuse(new ImportRejected($errors));
        }

        try {
            $result = $importer->import($json, ImportSource::Command, basename($path));
        } catch (ImportRejected $rejected) {
            return $this->refuse($rejected);
        }

        $this->components->info(__('Imported :file.', ['file' => basename($path)]));
        $this->table(
            [__('Range'), __('Lanes'), __('Cards created'), __('Cards replaced')],
            [[__(':from to :to', ['from' => $result->from, 'to' => $result->to]), $result->lanesUpserted, $result->entriesCreated, $result->entriesReplaced]],
        );

        return self::SUCCESS;
    }

    private function reportValid(string $json): int
    {
        /** @var array{range: array{from: string, to: string}, lanes: list<mixed>, entries: list<mixed>} $file */
        $file = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->components->info(__('The file is valid: :lanes lanes and :cards cards from :from to :to. Nothing was written.', [
            'lanes' => count($file['lanes']),
            'cards' => count($file['entries']),
            'from' => $file['range']['from'],
            'to' => $file['range']['to'],
        ]));

        return self::SUCCESS;
    }

    private function refuse(ImportRejected $rejected): int
    {
        $this->components->error($rejected->getMessage());
        // Plain lines, not a component list: those wrap long messages at the terminal width,
        // which splits a message the skill has to read back whole.
        foreach ($rejected->errors() as $problem) {
            $this->line('  - '.$problem);
        }

        return self::FAILURE;
    }
}
