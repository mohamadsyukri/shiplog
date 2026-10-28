{{-- items: label => url (null for the current page). Extra attributes (a class) go on the nav. --}}
@props(['items' => []])
@if ($items)
    <nav aria-label="breadcrumb" {{ $attributes }}>
        <ol class="breadcrumb small mb-1">
            @foreach ($items as $label => $url)
                @if ($url && ! $loop->last)
                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
