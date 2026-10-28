{{-- The command bar across every page: the menu, the mark, the page's breadcrumb, the theme switch and the account menu.
     A demo visitor is not signed in, so gets no menu or account, only the mark and the switch (config/shiplog.php). --}}
<header class="app-bar">
    @auth
        <button class="app-bar-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#app-sidebar" aria-controls="app-sidebar" aria-label="{{ __('Menu') }}">
            <i class="bi bi-list"></i>
        </button>
        <button class="app-bar-btn d-none d-lg-grid" type="button" data-nav-toggle aria-controls="app-sidebar" aria-expanded="true" aria-label="{{ __('Collapse the menu') }}">
            <i class="bi bi-list"></i>
        </button>
    @endauth

    <a href="{{ route('board.index') }}" class="app-brand">@include('layouts._logo')<span>{{ config('app.name') }}</span></a>

    <x-breadcrumb :items="$breadcrumbs ?? []" class="app-crumb d-none d-md-block" />

    {{-- Whoever may open the board may search it; on phones the field is on the board itself. --}}
    @if (auth()->check() || config('shiplog.demo'))
        @include('layouts._search', ['id' => 'app-search', 'class' => 'app-search d-none d-md-flex'])
    @endif

    <div class="app-bar-end">
        <div class="app-theme" role="group" aria-label="{{ __('Theme') }}">
            @foreach (['light' => ['bi-sun', __('Light')], 'dark' => ['bi-moon-stars', __('Dark')], 'auto' => ['bi-circle-half', __('Auto')]] as $choice => [$icon, $label])
                <button type="button" data-theme-choice="{{ $choice }}" aria-pressed="false" aria-label="{{ __(':theme theme', ['theme' => $label]) }}" title="{{ __(':theme theme', ['theme' => $label]) }}">
                    <i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                </button>
            @endforeach
        </div>

        @auth
            <div class="dropdown">
                <button class="app-avatar" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Account: :name', ['name' => auth()->user()->name]) }}">{{ auth()->user()->initials() }}</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header app-account">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->getRoleNames()->implode(', ') }}</span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>{{ __('Profile') }}</a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>{{ __('Sign out') }}</button>
                        </form>
                    </li>
                </ul>
            </div>
        @endauth
    </div>
</header>
