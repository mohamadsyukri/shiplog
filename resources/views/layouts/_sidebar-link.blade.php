{{-- The title names the link when the collapsed menu shows only its icon. --}}
<li>
    <a href="{{ $item['url'] }}" @class(['app-nav-link', 'active' => $item['active']]) @if ($item['active']) aria-current="page" @endif title="{{ $item['label'] }}">
        <i class="bi {{ $item['icon'] ?: 'bi-dot' }}" aria-hidden="true"></i>
        <span>{{ $item['label'] }}</span>
    </a>
</li>
