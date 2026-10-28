{{-- The menu pane, built from the menus table and filtered by what this user may open: App\Support\Menu\Sidebar.
     Below lg it is an offcanvas opened from the command bar; from lg the command bar collapses it to icons. --}}
<aside class="offcanvas-lg offcanvas-start app-nav" tabindex="-1" id="app-sidebar" aria-label="{{ __('Main menu') }}">
    <div class="offcanvas-header d-lg-none">
        <span class="app-brand">@include('layouts._logo'){{ config('app.name') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#app-sidebar" aria-label="{{ __('Close') }}"></button>
    </div>

    <nav class="offcanvas-body d-block app-nav-body">
        <ul class="app-nav-list">
            @foreach ($menuItems as $item)
                @if ($item['children'])
                    <li class="app-nav-group">
                        <span class="app-nav-heading">{{ $item['label'] }}</span>
                        <ul class="app-nav-list">
                            @foreach ($item['children'] as $child)
                                @include('layouts._sidebar-link', ['item' => $child])
                            @endforeach
                        </ul>
                    </li>
                @else
                    @include('layouts._sidebar-link', ['item' => $item])
                @endif
            @endforeach
        </ul>
    </nav>
</aside>
