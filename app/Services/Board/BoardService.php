<?php

declare(strict_types=1);

namespace App\Services\Board;

use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class BoardService
{
    public const int DAYS_PER_PAGE = 30;

    // The figures' longest period (30 days) and the period before it, which the change is measured against.
    public const int FIGURE_DAYS = 60;

    /**
     * Days with at least one card, newest first, starting before $before (or from the newest day).
     * Only days with work count, so a quiet fortnight doesn't push older work off the page.
     */
    public function page(?CarbonImmutable $before, int $days = self::DAYS_PER_PAGE): BoardPage
    {
        // Dates as stored (Y-m-d strings, see ImportService::import), so they match whereIn below.
        // One row past the page says whether an older page exists.
        $dates = Entry::query()
            ->when($before, fn (Builder $query, CarbonImmutable $day) => $query->where('date', '<', $day->toDateString()))
            ->distinct()
            ->orderByDesc('date')
            ->limit($days + 1)
            ->toBase()
            ->pluck('date');
        $hasOlder = $dates->count() > $days;
        $dates = $dates->take($days)->values();

        $cardsByDate = Entry::query()
            ->whereIn('date', $dates->all())
            ->get()
            ->groupBy(fn (Entry $entry): string => $entry->date->toDateString());

        // The newest day any import covered: a month backfilled later must not move it back.
        $updatedUpTo = Import::query()->max('range_to');
        $lanes = Lane::query()->orderBy('sort')->orderBy('name')->get();

        return new BoardPage(
            lanes: $lanes,
            days: $dates->map(fn (string $date): array => [
                'date' => CarbonImmutable::parse($date),
                'cards' => $cardsByDate->get($date, collect())->keyBy('lane_id')->all(),
            ])->all(),
            updatedUpTo: $updatedUpTo === null ? null : CarbonImmutable::parse($updatedUpTo),
            olderBefore: $hasOlder ? $dates->last() : null,
            // Only the newest page leads with the figures; older pages are for reading back.
            figures: $before === null && $dates->isNotEmpty() ? $this->figures($lanes, $updatedUpTo ?? $dates->first()) : null,
        );
    }

    /**
     * Every calendar day of the FIGURE_DAYS up to $until (Y-m-d, oldest first), with the sources of
     * each lane's card that day. resources/js/components/board-figures.js sums them for the chosen
     * period and lanes, so the figures follow the Lanes filter without another request.
     *
     * @param  Collection<int, Lane>  $lanes
     * @return array{until: string, lanes: list<array{key: string, name: string, color: string}>, days: list<array{date: string, sources: array<string, int>}>}
     */
    private function figures(Collection $lanes, string $until): array
    {
        $last = CarbonImmutable::parse($until);
        $first = $last->subDays(self::FIGURE_DAYS - 1);
        $laneKeys = $lanes->pluck('key', 'id');

        // Y-m-d strings both ways, as the cards are stored (ImportService::import).
        $cards = Entry::query()
            ->whereBetween('date', [$first->toDateString(), $last->toDateString()])
            ->get(['date', 'lane_id', 'sources'])
            ->groupBy(fn (Entry $entry): string => $entry->date->toDateString());

        return [
            'until' => $last->toDateString(),
            'lanes' => $lanes->map(fn (Lane $lane): array => ['key' => $lane->key, 'name' => $lane->name, 'color' => $lane->color])->values()->all(),
            'days' => collect(range(0, self::FIGURE_DAYS - 1))->map(function (int $offset) use ($first, $cards, $laneKeys): array {
                $date = $first->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'sources' => $cards->get($date, collect())
                        ->mapWithKeys(fn (Entry $entry): array => [$laneKeys[$entry->lane_id] => count($entry->sources)])
                        ->all(),
                ];
            })->all(),
        ];
    }
}
