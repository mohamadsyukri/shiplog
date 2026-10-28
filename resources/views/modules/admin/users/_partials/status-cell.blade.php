{{-- The Users list's status: a dot and the word, so it never rests on colour alone. --}}
<span @class(['app-status', 'is-off' => ! $active])><i aria-hidden="true"></i>{{ $active ? __('Active') : __('Inactive') }}</span>
