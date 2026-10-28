{{-- The Users list's name cell: the initials, then the name. --}}
<span class="app-person"><span class="app-person-initials" aria-hidden="true">{{ $user->initials() }}</span>{{ $user->name }}</span>
