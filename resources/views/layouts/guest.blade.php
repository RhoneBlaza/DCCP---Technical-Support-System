<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') — {{ settings('system_short_name', config('app.name')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dccp-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/dccp-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100">
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img
                    src="{{ asset('images/dccp-logo.png') }}"
                    alt="{{ settings('organization_name', 'Data Center College of the Philippines - Bangued') }} logo"
                    class="inline-block w-20 h-20 object-contain mb-4">
                <h1 class="text-xl font-bold text-slate-800">{{ settings('system_name', config('app.name')) }}</h1>
                <p class="text-sm text-slate-500 mt-1">Technical Support / IT Helpdesk</p>
            </div>

            <div class="bg-white rounded-lg border border-slate-200 p-6 sm:p-8">
                @yield('content')
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">{{ settings('organization_name', '') }}</p>
        </div>
    </div>
</body>
</html>