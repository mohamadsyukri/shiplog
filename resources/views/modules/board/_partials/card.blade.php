{{-- A lane's summary for one day. Its sources open in place: <details> needs no JavaScript. --}}
@php($icons = ['commit' => 'bi-git', 'session' => 'bi-chat-left-text', 'artifact' => 'bi-file-earmark-richtext', 'note' => 'bi-journal-text'])
<article class="board-card card shadow-sm" style="--lane-color: {{ $lane->color }}">
    <div class="card-body p-3">
        {{-- Phones stack the cards and hide the lane headers, so each card names its lane. --}}
        <div class="board-card-lane small fw-semibold mb-1">{{ $lane->name }}</div>
        <p class="board-summary mb-2">{{ $entry->summary }}</p>

        <details class="board-sources small">
            <summary class="text-body-secondary">{{ trans_choice(':count source|:count sources', count($entry->sources)) }}</summary>
            <ul class="list-unstyled mb-0 mt-2">
                @foreach ($entry->sources as $source)
                    <li class="d-flex gap-2 py-1">
                        <i class="bi {{ $icons[$source['type']] ?? 'bi-dot' }} text-body-secondary" aria-hidden="true"></i>
                        <span class="text-break">
                            <span class="visually-hidden">{{ $source['type'] }}:</span>
                            @if ($source['url'] ?? null)
                                <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['label'] }}</a>
                            @else
                                {{ $source['label'] }}
                            @endif
                            <code class="text-body-secondary ms-1">{{ $source['ref'] }}</code>
                        </span>
                    </li>
                @endforeach
            </ul>
        </details>
    </div>
</article>
