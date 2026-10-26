<?php

declare(strict_types=1);

namespace App\Services\Imports;

use App\Enums\ImportSource;
use App\Exceptions\Imports\ImportRejected;
use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Reads a Shiplog file (format shiplog/v1, shown in the README) for both the shiplog:import command
 * and the upload screen, so the two accept and refuse exactly the same files. A file is refused
 * whole: one wrong card and nothing is written. An import never deletes a card.
 */
final readonly class ImportService
{
    public const string FORMAT = 'shiplog/v1';

    public const int SUMMARY_MAX = 600;

    /** @var list<string> */
    public const array SOURCE_TYPES = ['commit', 'session', 'artifact', 'note'];

    // Column widths of lanes.key, lanes.name and lanes.sort (unsigned smallint).
    private const int KEY_MAX = 40;

    private const int NAME_MAX = 80;

    private const int ORDER_MAX = 65535;

    // Lowercase only: MySQL's default collation compares keys case-insensitively, so "Other" and
    // "other" would pass a case-sensitive duplicate check here and then collide on the unique index.
    private const string KEY_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const string COLOR_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    /**
     * @return list<string> one message per problem; empty when the file can be imported
     */
    public function validate(string $json): array
    {
        try {
            $file = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return [__('The file is not valid JSON (:reason).', ['reason' => $e->getMessage()])];
        }

        if (! $this->isObject($file)) {
            return [__('The file must hold one JSON object.')];
        }

        // Format first: a file in another format may name its fields differently, and listing
        // each of them as unknown would bury the one message that matters.
        if (! array_key_exists('format', $file)) {
            return [__('The file has no format; this version of Shiplog reads ":format".', ['format' => self::FORMAT])];
        }
        if ($file['format'] !== self::FORMAT) {
            return [__('format :value is not supported; this version of Shiplog reads ":format".', ['value' => $this->show($file['format']), 'format' => self::FORMAT])];
        }

        $errors = $this->fieldErrors($file, ['format', 'range', 'lanes', 'entries'], [], __('The file'));
        foreach (['lanes', 'entries'] as $list) {
            if (array_key_exists($list, $file) && ! (is_array($file[$list]) && array_is_list($file[$list]))) {
                $errors[] = __(':list must be a list.', ['list' => $list]);
            }
        }
        if ($errors !== []) {
            return $errors;
        }

        [$from, $to] = $this->checkRange($file['range'], $errors);
        $laneKeys = $this->checkLanes($file['lanes'], $errors);
        $this->checkEntries($file['entries'], $from, $to, $laneKeys, $errors);

        return $errors;
    }

    /**
     * @throws ImportRejected when validate() finds any problem; nothing is written then
     */
    public function import(string $json, ImportSource $source, string $fileName, ?User $by = null): ImportResult
    {
        $errors = $this->validate($json);
        if ($errors !== []) {
            throw new ImportRejected($errors);
        }

        /** @var array{range: array{from: string, to: string}, lanes: list<array{key: string, name: string, color: string, order: int}>, entries: list<array{date: string, lane: string, summary: string, sources: list<array{type: string, ref: string, label: string, url?: ?string}>}>} $file */
        $file = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return DB::transaction(function () use ($file, $source, $fileName, $by): ImportResult {
            $laneIds = [];
            foreach ($file['lanes'] as $lane) {
                $laneIds[$lane['key']] = Lane::query()->updateOrCreate(
                    ['key' => $lane['key']],
                    ['name' => trim($lane['name']), 'color' => $lane['color'], 'sort' => $lane['order']],
                )->id;
            }

            $import = Import::query()->create([
                'file_name' => $fileName,
                'range_from' => $file['range']['from'],
                'range_to' => $file['range']['to'],
                'format' => self::FORMAT,
                'source' => $source,
                'lanes_upserted' => count($laneIds),
                'user_id' => $by?->id,
            ]);

            $created = 0;
            $replaced = 0;
            foreach ($file['entries'] as $card) {
                // The date stays a Y-m-d string: the date:Y-m-d cast stores what it is given, and a
                // Carbon here would be written with a time and miss this lookup on the next import.
                $entry = Entry::query()->firstOrNew(['date' => $card['date'], 'lane_id' => $laneIds[$card['lane']]]);
                if ($entry->exists) {
                    $replaced++;
                } else {
                    $created++;
                }
                $entry->fill([
                    'summary' => trim($card['summary']),
                    'sources' => array_map(fn (array $record): array => [
                        'type' => $record['type'],
                        'ref' => $record['ref'],
                        'label' => $record['label'],
                        'url' => $record['url'] ?? null,
                    ], $card['sources']),
                    'import_id' => $import->id,
                ])->save();
            }

            $import->update(['entries_created' => $created, 'entries_replaced' => $replaced]);

            return new ImportResult($file['range']['from'], $file['range']['to'], count($laneIds), $created, $replaced);
        });
    }

    /**
     * @param  list<string>  $errors
     * @return array{0: ?string, 1: ?string} from and to, or nulls when the range can't be used
     */
    private function checkRange(mixed $range, array &$errors): array
    {
        if (! $this->isObject($range)) {
            $errors[] = __('range must be an object with from and to.');

            return [null, null];
        }

        $problems = $this->fieldErrors($range, ['from', 'to'], [], 'range');
        foreach (['from', 'to'] as $end) {
            if (array_key_exists($end, $range) && ! $this->isDate($range[$end])) {
                $problems[] = __('range.:end :value is not a date written YYYY-MM-DD.', ['end' => $end, 'value' => $this->show($range[$end])]);
            }
        }
        if ($problems === [] && $range['from'] > $range['to']) {
            $problems[] = __('range.from :from is after range.to :to.', ['from' => $range['from'], 'to' => $range['to']]);
        }

        array_push($errors, ...$problems);

        return $problems === [] ? [$range['from'], $range['to']] : [null, null];
    }

    /**
     * @param  list<mixed>  $lanes
     * @param  list<string>  $errors
     * @return list<string> the keys listed, so each card's lane can be checked against them
     */
    private function checkLanes(array $lanes, array &$errors): array
    {
        $keys = [];
        foreach ($lanes as $i => $lane) {
            $where = "lanes[{$i}]";
            if (! $this->isObject($lane)) {
                $errors[] = __(':where is not an object.', ['where' => $where]);

                continue;
            }
            array_push($errors, ...$this->fieldErrors($lane, ['key', 'name', 'color', 'order'], [], $where));

            if (array_key_exists('key', $lane)) {
                $key = $lane['key'];
                if (! is_string($key) || strlen($key) > self::KEY_MAX || ! preg_match(self::KEY_PATTERN, $key)) {
                    $errors[] = __(':where key :value must be 1 to :max lowercase letters, digits or hyphens.', ['where' => $where, 'value' => $this->show($key), 'max' => self::KEY_MAX]);
                } elseif (in_array($key, $keys, true)) {
                    $errors[] = __(':where key ":key" is listed twice.', ['where' => $where, 'key' => $key]);
                } else {
                    $keys[] = $key;
                }
            }
            if (array_key_exists('name', $lane) && ! $this->isText($lane['name'], self::NAME_MAX)) {
                $errors[] = __(':where name must be text of 1 to :max characters.', ['where' => $where, 'max' => self::NAME_MAX]);
            }
            if (array_key_exists('color', $lane) && ! (is_string($lane['color']) && preg_match(self::COLOR_PATTERN, $lane['color']))) {
                $errors[] = __(':where color :value is not written #rrggbb.', ['where' => $where, 'value' => $this->show($lane['color'])]);
            }
            if (array_key_exists('order', $lane) && ! (is_int($lane['order']) && $lane['order'] >= 0 && $lane['order'] <= self::ORDER_MAX)) {
                $errors[] = __(':where order :value must be a whole number from 0 to :max.', ['where' => $where, 'value' => $this->show($lane['order']), 'max' => self::ORDER_MAX]);
            }
        }

        return $keys;
    }

    /**
     * @param  list<mixed>  $entries
     * @param  list<string>  $laneKeys
     * @param  list<string>  $errors
     */
    private function checkEntries(array $entries, ?string $from, ?string $to, array $laneKeys, array &$errors): void
    {
        $seen = [];
        foreach ($entries as $i => $entry) {
            if (! $this->isObject($entry)) {
                $errors[] = __(':where is not an object.', ['where' => "entries[{$i}]"]);

                continue;
            }

            $date = $entry['date'] ?? null;
            $lane = $entry['lane'] ?? null;
            // Each message names the card by its date and lane as well as its position, so it can
            // be found in a long file without counting.
            $where = "entries[{$i}]".(is_string($date) ? " {$date}" : '').(is_string($lane) ? " lane \"{$lane}\"" : '');
            array_push($errors, ...$this->fieldErrors($entry, ['date', 'lane', 'summary', 'sources'], [], $where));

            if (array_key_exists('date', $entry)) {
                if (! $this->isDate($date)) {
                    $errors[] = __(':where date is not a date written YYYY-MM-DD.', ['where' => $where]);
                } elseif ($from !== null && ($date < $from || $date > $to)) {
                    $errors[] = __(':where is outside the range :from to :to.', ['where' => $where, 'from' => $from, 'to' => $to]);
                }
            }
            if (array_key_exists('lane', $entry)) {
                if (! is_string($lane)) {
                    $errors[] = __(':where lane must be text.', ['where' => $where]);
                } elseif (! in_array($lane, $laneKeys, true)) {
                    $errors[] = __(':where is not in lanes.', ['where' => $where]);
                }
            }
            if (is_string($date) && is_string($lane)) {
                if (isset($seen[$date][$lane])) {
                    $errors[] = __(':where is a second card for the same day and lane.', ['where' => $where]);
                }
                $seen[$date][$lane] = true;
            }
            if (array_key_exists('summary', $entry)) {
                $this->checkSummary($entry['summary'], $where, $errors);
            }
            if (array_key_exists('sources', $entry)) {
                $this->checkSources($entry['sources'], $where, $errors);
            }
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function checkSummary(mixed $summary, string $where, array &$errors): void
    {
        if (! is_string($summary)) {
            $errors[] = __(':where summary must be text.', ['where' => $where]);
        } elseif (trim($summary) === '') {
            $errors[] = __(':where summary is empty.', ['where' => $where]);
        } elseif (mb_strlen(trim($summary)) > self::SUMMARY_MAX) {
            $errors[] = __(':where summary is :length characters; the limit is :max.', ['where' => $where, 'length' => mb_strlen(trim($summary)), 'max' => self::SUMMARY_MAX]);
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function checkSources(mixed $sources, string $where, array &$errors): void
    {
        if (! is_array($sources) || ! array_is_list($sources)) {
            $errors[] = __(':where sources must be a list.', ['where' => $where]);

            return;
        }
        if ($sources === []) {
            $errors[] = __(':where has no sources; every card comes from at least one record.', ['where' => $where]);

            return;
        }

        foreach ($sources as $j => $record) {
            $at = "{$where} sources[{$j}]";
            if (! $this->isObject($record)) {
                $errors[] = __(':where is not an object.', ['where' => $at]);

                continue;
            }
            array_push($errors, ...$this->fieldErrors($record, ['type', 'ref', 'label'], ['url'], $at));

            if (array_key_exists('type', $record) && ! in_array($record['type'], self::SOURCE_TYPES, true)) {
                $errors[] = __(':where type :value is not one of :types.', ['where' => $at, 'value' => $this->show($record['type']), 'types' => implode(', ', self::SOURCE_TYPES)]);
            }
            foreach (['ref', 'label'] as $field) {
                if (array_key_exists($field, $record) && ! $this->isText($record[$field])) {
                    $errors[] = __(':where :field must be text.', ['where' => $at, 'field' => $field]);
                }
            }
            // The board shows the url as a link, so only web addresses: a javascript: one would run
            // in the browser of whoever clicks it.
            if (($record['url'] ?? null) !== null && ! $this->isWebAddress($record['url'])) {
                $errors[] = __(':where url must be an http or https address, or null.', ['where' => $at]);
            }
        }
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @param  list<string>  $required
     * @param  list<string>  $optional
     * @return list<string>
     */
    private function fieldErrors(array $item, array $required, array $optional, string $where): array
    {
        $errors = [];
        foreach ($required as $field) {
            if (! array_key_exists($field, $item)) {
                $errors[] = __(':where is missing :field.', ['where' => $where, 'field' => $field]);
            }
        }
        foreach (array_keys($item) as $field) {
            if (! in_array($field, [...$required, ...$optional], true)) {
                $errors[] = __(':where has an unknown field :field.', ['where' => $where, 'field' => $this->show((string) $field)]);
            }
        }

        return $errors;
    }

    /**
     * json_decode gives an array for both {} and [], so an empty one counts as an object here and
     * then fails on its missing fields instead.
     */
    private function isObject(mixed $value): bool
    {
        return is_array($value) && ($value === [] || ! array_is_list($value));
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        // The round trip catches dates PHP would roll over, such as 2026-02-30.
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function isText(mixed $value, ?int $max = null): bool
    {
        return is_string($value) && trim($value) !== '' && ($max === null || mb_strlen(trim($value)) <= $max);
    }

    private function isWebAddress(mixed $value): bool
    {
        return is_string($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * A value from the file as it was written there, quotes included, for messages.
     */
    private function show(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
