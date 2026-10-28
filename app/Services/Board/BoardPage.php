<?php

declare(strict_types=1);

namespace App\Services\Board;

use App\Models\Entry;
use App\Models\Lane;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * One page of the board, ready for the view.
 */
final readonly class BoardPage
{
    /**
     * @param  Collection<int, Lane>  $lanes  the columns, in lane order
     * @param  list<array{date: CarbonImmutable, cards: array<int, Entry>}>  $days  newest first; cards keyed by lane id
     * @param  ?string  $olderBefore  the ?before= date (Y-m-d) of the "Older days" link; null on the last page
     * @param  ?array<string, mixed>  $figures  the figures' data, shaped by BoardService::figures(); null on older pages
     */
    public function __construct(
        public Collection $lanes,
        public array $days,
        public ?CarbonImmutable $updatedUpTo,
        public ?string $olderBefore,
        public ?array $figures,
    ) {}
}
