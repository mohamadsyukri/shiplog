{{--
    The details pane: a card's summary and sources, filled from its template by resources/js/components/board.js.
    No backdrop and the page keeps scrolling, so another card can be opened while it is showing.
--}}
<aside class="offcanvas offcanvas-end board-pane" tabindex="-1" id="board-pane" aria-labelledby="board-pane-title"
    data-bs-backdrop="false" data-bs-scroll="true" data-copied="{{ __('Copied') }}" data-copy-failed="{{ __('Could not copy. Select the reference and copy it yourself.') }}">
    <div class="offcanvas-header board-pane-head">
        <div class="board-pane-heading">
            <h2 class="offcanvas-title h6 mb-0" id="board-pane-title">
                <span class="board-swatch" aria-hidden="true"></span>
                <span data-pane-title></span>
            </h2>
            <p class="board-pane-date mb-0" data-pane-date></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close the details') }}"></button>
    </div>
    <div class="offcanvas-body" data-pane-body></div>
    <div class="board-pane-steps">
        <x-button variant="outline-secondary" icon="bi-arrow-up" data-step="newer">{{ __('Newer') }}</x-button>
        <x-button variant="outline-secondary" icon="bi-arrow-down" data-step="older">{{ __('Older') }}</x-button>
    </div>
</aside>
