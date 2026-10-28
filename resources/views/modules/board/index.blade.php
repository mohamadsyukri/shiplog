<x-layouts.app :title="__('Board')">
    @push('styles')
        @vite('resources/css/board/board.css')
    @endpush

    @if ($page->updatedUpTo)
        <p class="small text-body-secondary mb-3">{{ __('Updated up to :date', ['date' => $page->updatedUpTo->format('d/m/Y')]) }}</p>
    @endif

    @if ($page->days === [])
        <x-card>
            @if ($before)
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
        <x-card :title="__('Diary')">
            <x-slot:actions>
                @include('modules.board._partials.lane-filter', ['lanes' => $page->lanes])
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
                        <x-button variant="outline-secondary" icon="bi-arrow-up" :href="route('board.index')">{{ __('Newest days') }}</x-button>
                    @endif
                    @if ($page->olderBefore)
                        <x-button variant="outline-secondary" icon="bi-arrow-down" :href="route('board.index', ['before' => $page->olderBefore])">{{ __('Older days') }}</x-button>
                    @endif
                </nav>
            @endif
        </x-card>

        @include('modules.board._partials.pane')
    @endif
</x-layouts.app>
