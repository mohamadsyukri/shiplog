<x-layouts.app :title="__('Board')">
    @push('styles')
        @vite('resources/css/board/board.css')
    @endpush

    @if ($page->updatedUpTo)
        <p class="small text-body-secondary mb-3">{{ __('Updated up to :date', ['date' => $page->updatedUpTo->format('d/m/Y')]) }}</p>
    @endif

    {{-- From md the search field is in the command bar (layouts/_topbar). --}}
    @include('layouts._search', ['id' => 'board-search', 'class' => 'board-search d-md-none'])

    @if ($page->days === [])
        <x-card>
            @if ($search !== null)
                <p class="mb-0">
                    {{ __('No cards match “:search”.', ['search' => $search]) }}
                    <a href="{{ route('board.index') }}">{{ __('Clear the search') }}</a>
                </p>
            @elseif ($before)
                <p class="mb-0">
                    {{ __('No days with work before :date.', ['date' => $before->format('d/m/Y')]) }}
                    <a href="{{ route('board.index') }}">{{ __('Newest days') }}</a>
                </p>
            @else
                <h2 class="h5">{{ __('No cards yet.') }}</h2>
                @if (config('shiplog.demo'))
                    <p>{{ __('This demo has no data yet. Fill it with:') }}</p>
                    <pre class="mb-0"><code>php artisan db:seed --class=DemoSeeder</code></pre>
                @else
                    <p>{{ __('Ask Claude Code to "update my board" with the Shiplog skill, or import a file you wrote yourself:') }}</p>
                    <pre class="mb-0"><code>php artisan shiplog:import path/to/shiplog-file.json</code></pre>
                    @can('imports.create')
                        <p class="mt-3 mb-0">{{ __('Or upload it:') }} <a href="{{ route('imports.create') }}">{{ __('Upload a Shiplog file') }}</a></p>
                    @endcan
                @endif
            @endif
        </x-card>
    @else
        @if ($search !== null)
            <p class="board-search-note">
                {{ __('Cards matching “:search”.', ['search' => $search]) }}
                <a href="{{ route('board.index') }}">{{ __('Clear the search') }}</a>
            </p>
        @endif

        {{-- One row of filters above everything they scope: the figures follow both, the diary its lanes. --}}
        <div class="board-filters">
            @include('modules.board._partials.lane-filter', ['lanes' => $page->lanes])
            @if ($page->figures)
                <div class="btn-group board-period" role="group" aria-label="{{ __('Period') }}">
                    @foreach ([14, 30] as $periodDays)
                        <button type="button" class="btn btn-outline-secondary" data-period="{{ $periodDays }}" aria-pressed="{{ $periodDays === 30 ? 'true' : 'false' }}">{{ __('Last :days days', ['days' => $periodDays]) }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($page->figures)
            @include('modules.board._partials.figures', ['figures' => $page->figures])
        @endif

        <x-card :title="__('Diary')">
            <x-slot:actions>
                <span class="small text-body-secondary">{{ __('Newest first. Select a card for its sources.') }}</span>
            </x-slot:actions>

            {{-- The size container: below 54rem the days stack (board.css). --}}
            <div class="board-frame">
                <div class="board" data-board style="--board-lanes: {{ $page->lanes->count() }}">
                    <div class="board-row board-head" aria-hidden="true">
                        <div class="board-head-day">{{ __('Day') }}</div>
                        @foreach ($page->lanes as $lane)
                            <div class="board-lane" data-lane="{{ $lane->key }}" style="--lane-color: {{ $lane->color }}">
                                <span class="board-swatch"></span>
                                <span class="text-truncate">{{ $lane->name }}</span>
                                <span class="board-lane-count">{{ collect($page->days)->filter(fn (array $day): bool => isset($day['cards'][$lane->id]))->count() }}</span>
                            </div>
                        @endforeach
                    </div>

                    @foreach ($page->days as $day)
                        @include('modules.board._partials.day-row', ['day' => $day, 'lanes' => $page->lanes])
                    @endforeach
                </div>

                <p class="board-message" data-board-none hidden>{{ __('Choose at least one lane in Lanes.') }}</p>
                <p class="board-message" data-board-quiet hidden>{{ __('The lanes you chose have no cards on this page.') }}</p>
            </div>

            @if ($before || $page->olderBefore)
                <nav class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Board pages') }}">
                    @if ($before)
                        <x-button variant="outline-secondary" icon="bi-arrow-up" :href="route('board.index', ['q' => $search])">{{ __('Newest days') }}</x-button>
                    @endif
                    @if ($page->olderBefore)
                        <x-button variant="outline-secondary" icon="bi-arrow-down" :href="route('board.index', ['before' => $page->olderBefore, 'q' => $search])">{{ __('Older days') }}</x-button>
                    @endif
                </nav>
            @endif
        </x-card>

        @include('modules.board._partials.pane')
    @endif
</x-layouts.app>
