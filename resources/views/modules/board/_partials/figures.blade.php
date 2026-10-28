{{--
    The figures above the diary, for the chosen period and lanes: four tiles and two charts, drawn by
    resources/js/components/board-figures.js from window.boardFigures (BoardService::figures). The
    sentences it fills in are here, so the screen text stays in __().
--}}
<section class="board-figures" aria-label="{{ __('Figures') }}" data-board-figures
    data-of="{{ __('of :days') }}"
    data-more="{{ __(':count more than the :days days before') }}"
    data-fewer="{{ __(':count fewer than the :days days before') }}"
    data-same="{{ __('Same as the :days days before') }}"
    data-share="{{ __(':percent% of the cards') }}"
    data-no-cards="{{ __('No cards in this period') }}"
    data-no-lanes="{{ __('Choose at least one lane in Lanes.') }}"
    data-range-label="{{ __(':from to :to') }}"
    data-chart-label="{{ __('Cards per day, :from to :to. The same numbers are in the table below.') }}"
    data-source-one="{{ __(':count source') }}"
    data-source-many="{{ __(':count sources') }}"
    data-no-card="{{ __('No card') }}"
    data-day-label="{{ __('Day') }}">
    <div class="board-kpis">
        <div class="card board-kpi" data-kpi="days">
            <span class="board-kpi-label">{{ __('Days with work') }}</span>
            <span class="board-kpi-value" data-value></span>
            <span class="board-kpi-delta" data-delta></span>
            <svg class="board-spark" data-spark aria-hidden="true"></svg>
        </div>
        <div class="card board-kpi" data-kpi="cards">
            <span class="board-kpi-label">{{ __('Cards') }}</span>
            <span class="board-kpi-value" data-value></span>
            <span class="board-kpi-delta" data-delta></span>
            <svg class="board-spark" data-spark aria-hidden="true"></svg>
        </div>
        <div class="card board-kpi" data-kpi="sources">
            <span class="board-kpi-label">{{ __('Sources behind them') }}</span>
            <span class="board-kpi-value" data-value></span>
            <span class="board-kpi-delta" data-delta></span>
            <svg class="board-spark" data-spark aria-hidden="true"></svg>
        </div>
        <div class="card board-kpi" data-kpi="busiest">
            <span class="board-kpi-label">{{ __('Busiest lane') }}</span>
            <span class="board-kpi-value board-kpi-name" data-value></span>
            <span class="board-kpi-delta" data-delta></span>
        </div>
    </div>

    <div class="board-charts">
        <x-card :title="__('Cards per day')">
            <x-slot:actions>
                <span class="small text-body-secondary" data-range></span>
            </x-slot:actions>
            {{-- Focusable so the arrow keys can walk the days; screen readers get the table instead. --}}
            <div class="board-chart" data-chart tabindex="0" role="img"></div>
            <div class="board-legend" data-legend></div>
            <details class="board-table-view">
                <summary>{{ __('Show as a table') }}</summary>
                <div class="table-responsive mt-2" data-table></div>
            </details>
        </x-card>

        <x-card :title="__('Cards by lane')">
            <x-slot:actions>
                <span class="small text-body-secondary" data-period-label></span>
            </x-slot:actions>
            <ul class="board-lanebars" data-lanebars></ul>
        </x-card>
    </div>
</section>

<script>
    window.boardFigures = @json($figures);
</script>
