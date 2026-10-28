{{-- Searches the diary's summaries and sources (board.index?q=, BoardService::matching). $id names the field; $class places it. --}}
@php($searchValue = request()->routeIs('board.index') && is_string(request()->query('q')) ? request()->query('q') : '')
<form method="GET" action="{{ route('board.index') }}" role="search" @class([$class ?? null])>
    <label class="visually-hidden" for="{{ $id }}">{{ __('Search the diary') }}</label>
    <i class="bi bi-search" aria-hidden="true"></i>
    <input type="search" name="q" id="{{ $id }}" value="{{ $searchValue }}" placeholder="{{ __('Search the diary') }}" maxlength="100" autocomplete="off">
</form>
