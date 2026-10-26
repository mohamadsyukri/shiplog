<?php

declare(strict_types=1);

namespace App\Services\Board;

use App\Models\Entry;
use App\Models\Import;
use App\Models\Lane;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class BoardService
{
    public const int DAYS_PER_PAGE = 30;

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

        return new BoardPage(
            lanes: Lane::query()->orderBy('sort')->orderBy('name')->get(),
            days: $dates->map(fn (string $date): array => [
                'date' => CarbonImmutable::parse($date),
                'cards' => $cardsByDate->get($date, collect())->keyBy('lane_id')->all(),
            ])->all(),
            updatedUpTo: $updatedUpTo === null ? null : CarbonImmutable::parse($updatedUpTo),
            olderBefore: $hasOlder ? $dates->last() : null,
        );
    }
}
