{{--
    The Lanes filter: hides lanes on this screen only. resources/js/components/board.js applies it and
    remembers it in this browser; nothing is sent to the server.
--}}
<div class="dropdown board-filter" data-lane-filter>
    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        {{ __('Lanes:') }}
        <strong class="board-filter-shown" data-filter-label data-all="{{ __('All') }}" data-none="{{ __('None') }}">{{ __('All') }}</strong>
    </button>
    <div class="dropdown-menu dropdown-menu-end board-filter-menu">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="board-lanes-all" data-filter-all checked>
            <label class="form-check-label" for="board-lanes-all">{{ __('Select all') }}</label>
        </div>
        <hr class="dropdown-divider">
        @foreach ($lanes as $lane)
            <div class="form-check" style="--lane-color: {{ $lane->color }}">
                <input class="form-check-input" type="checkbox" id="board-lane-{{ $lane->key }}" value="{{ $lane->key }}" data-name="{{ $lane->name }}" data-filter-lane checked>
                <label class="form-check-label" for="board-lane-{{ $lane->key }}"><span class="board-swatch" aria-hidden="true"></span>{{ $lane->name }}</label>
            </div>
        @endforeach
    </div>
</div>
