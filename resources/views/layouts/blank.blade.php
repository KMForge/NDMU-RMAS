<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} - Authentication</title>
    <x-favicon />

    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f4f7f6] text-slate-900 antialiased">
    @yield('content')
    <x-portal-onboarding />
    <x-portal-mobile-navigation />
    <x-logout-confirmation />
    @livewireScripts
    @stack('scripts')
</body>
</html>
