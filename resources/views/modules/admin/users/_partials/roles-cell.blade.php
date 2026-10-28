{{-- The Users list's roles, one pill each. --}}
@foreach ($roles as $role)
    <span class="app-pill">{{ $role }}</span>
@endforeach
