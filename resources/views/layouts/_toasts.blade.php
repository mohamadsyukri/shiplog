{{-- Session flashes, shown on load by resources/js/components/toast.js (window.toast adds more, with the same markup). --}}
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3">
    @foreach (['success' => 'bi-check-circle-fill text-success', 'error' => 'bi-x-circle-fill text-danger'] as $key => $icon)
        @if (session($key))
            <div class="toast app-toast" role="status" aria-live="polite">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi {{ $icon }} app-toast-icon" aria-hidden="true"></i>
                    <div class="toast-body flex-grow-1 p-0">{{ session($key) }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
                </div>
            </div>
        @endif
    @endforeach
</div>
