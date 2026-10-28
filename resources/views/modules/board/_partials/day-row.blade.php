{{-- One day: the date, then a cell per lane — its card, or an empty cell when that lane had no work. --}}
<section class="board-row" data-day aria-label="{{ $day['date']->format('l d/m/Y') }}">
    <h2 class="board-date h6 mb-0">
        <span class="board-weekday">{{ $day['date']->format('D') }}</span>
        <span class="board-dmy">{{ $day['date']->format('d/m/Y') }}</span>
    </h2>

    @foreach ($lanes as $lane)
        @isset($day['cards'][$lane->id])
            <div class="board-cell" data-lane="{{ $lane->key }}">
                @include('modules.board._partials.card', ['entry' => $day['cards'][$lane->id], 'lane' => $lane, 'date' => $day['date']])
            </div>
        @else
            <div class="board-cell board-empty" data-lane="{{ $lane->key }}">
                <span class="visually-hidden">{{ __(':lane: no card', ['lane' => $lane->name]) }}</span>
            </div>
        @endisset
    @endforeach
</section>
