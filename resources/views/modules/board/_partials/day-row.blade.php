{{-- One day: the date, then a cell per lane — its card, or a dash when that lane had no work. --}}
<section class="board-row" aria-label="{{ $day['date']->format('l d/m/Y') }}">
    <h2 class="board-date h6 mb-0">
        <span class="text-body-secondary">{{ $day['date']->format('D') }}</span> {{ $day['date']->format('d/m/Y') }}
    </h2>

    @foreach ($lanes as $lane)
        @isset($day['cards'][$lane->id])
            @include('modules.board._partials.card', ['entry' => $day['cards'][$lane->id], 'lane' => $lane])
        @else
            <div class="board-empty">
                <span aria-hidden="true">—</span>
                <span class="visually-hidden">{{ __(':lane: no card', ['lane' => $lane->name]) }}</span>
            </div>
        @endisset
    @endforeach
</section>
