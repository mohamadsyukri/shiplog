@props([
    'title' => null,
    'breadcrumbs' => [],
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @include('layouts._theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @include('layouts._topbar')

    <div class="app-shell">
        {{-- The menu is built for a signed-in user; a demo visitor has none (config/shiplog.php). --}}
        @auth
            @include('layouts._sidebar')
        @endauth

        <div class="app-main">
            @if (config('shiplog.demo'))
                <div class="app-demo-strip" role="note">
                    <strong>{{ __('Demo — made-up data.') }}</strong>
                    {{ __('Shiplog is open source:') }}
                    <a href="{{ config('shiplog.repository_url') }}">{{ config('shiplog.repository_url') }}</a>
                </div>
            @endif

            <main class="app-content">
                @if ($title || $breadcrumbs)
                    <div class="mb-3">
                        {{-- From md the breadcrumb rides in the command bar (layouts/_topbar). --}}
                        <x-breadcrumb :items="$breadcrumbs" class="d-md-none" />
                        @if ($title)
                            <h1 class="app-title">{{ $title }}</h1>
                        @endif
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @include('layouts._toasts')
    @include('layouts._confirm-modal')
</body>
</html>
