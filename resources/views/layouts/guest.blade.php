<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') — {{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dccp-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/dccp-logo.png') }}">
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-page">
    <x-theme-toggle placement="floating" />

    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img
                    src="{{ asset('images/dccp-logo.png') }}"
                    alt="{{ $organizationName }} logo"
                    class="inline-block w-20 h-20 object-contain mb-4">
                <h1 class="text-xl font-bold text-ink">{{ $systemName }}</h1>
                <p class="text-sm text-ink-subtle mt-1">Technical Support / IT Helpdesk</p>
            </div>

            <div class="bg-surface rounded-lg border border-line p-6 sm:p-8">
                @yield('content')
            </div>
        </div>
    </div>

    <footer class="border-t border-line bg-surface px-4 py-3 text-xs text-ink-subtle shrink-0">
        <div class="max-w-md mx-auto w-full text-center space-y-0.5">
            <p>{{ $systemName }}</p>
            <p class="text-ink-faint">{{ $organizationName }}</p>
        </div>
    </footer>
</body>
</html>