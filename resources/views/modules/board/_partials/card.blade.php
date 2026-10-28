{{--
    A lane's summary for one day: a button that opens its details in the side pane (_partials/pane).
    The details wait in the template beside it, so opening one needs no request.
--}}
@php($icons = ['commit' => 'bi-git', 'session' => 'bi-chat-left-text', 'artifact' => 'bi-file-earmark-richtext', 'note' => 'bi-journal-text'])
<button type="button" class="board-card" style="--lane-color: {{ $lane->color }}" data-card="board-card-{{ $entry->id }}" aria-controls="board-pane" aria-expanded="false">
    {{-- Stacked days hide the lane headers, so each card names its lane there. --}}
    <span class="board-card-lane"><span class="board-swatch"></span>{{ $lane->name }}</span>
    <span class="board-summary">{{ $entry->summary }}</span>
    <span class="board-card-meta">{{ trans_choice(':count source|:count sources', count($entry->sources)) }}</span>
</button>

<template id="board-card-{{ $entry->id }}" data-title="{{ $lane->name }}" data-date="{{ $date->format('l d/m/Y') }}">
    <p class="board-pane-summary">{{ $entry->summary }}</p>
    <h3 class="board-pane-label">{{ __('Sources (:count)', ['count' => count($entry->sources)]) }}</h3>
    <ul class="board-sources">
        @foreach ($entry->sources as $source)
            <li>
                <i class="bi {{ $icons[$source['type']] ?? 'bi-dot' }}" aria-hidden="true"></i>
                <span class="text-break">
                    <span class="visually-hidden">{{ $source['type'] }}:</span>
                    @if ($source['url'] ?? null)
                        <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['label'] }}</a>
                    @else
                        {{ $source['label'] }}
                    @endif
                    <code class="board-ref">{{ $source['ref'] }}</code>
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="{{ $source['ref'] }}">{{ __('Copy') }}</button>
            </li>
        @endforeach
    </ul>
</template>
