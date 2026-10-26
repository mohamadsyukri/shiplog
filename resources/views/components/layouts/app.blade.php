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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
</head>
<body>
    <div class="d-lg-flex">
        {{-- The sidebar is built for a signed-in user; a demo visitor has none (config/shiplog.php). --}}
        @auth
            @include('layouts._sidebar')
        @endauth

        <div class="app-main flex-grow-1">
            @include('layouts._topbar')

            @if (config('shiplog.demo'))
                <div class="alert alert-info border-0 border-bottom rounded-0 mb-0 small" role="note">
                    <strong>{{ __('Demo — made-up data.') }}</strong>
                    {{ __('Shiplog is open source:') }}
                    <a href="{{ config('shiplog.repository_url') }}">{{ config('shiplog.repository_url') }}</a>
                </div>
            @endif

            <main class="container-fluid py-4">
                @if ($title || $breadcrumbs)
                    <div class="mb-3">
                        <x-breadcrumb :items="$breadcrumbs" />
                        @if ($title)
                            <h1 class="h4 mb-0">{{ $title }}</h1>
                        @endif
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @include('layouts._toasts')
    @include('layouts._confirm-modal')

    @php($dataTableLanguage = trans('datatables'))
    <script>
        window.dataTableLanguage = @json(is_array($dataTableLanguage) ? $dataTableLanguage : []);
    </script>
    @livewireScripts
</body>
</html>
