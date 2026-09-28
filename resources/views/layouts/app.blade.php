<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', settings('system_name', config('app.name'))) — {{ settings('system_short_name', config('app.name')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dccp-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/dccp-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full">
    <div class="min-h-full flex" x-data="{ sidebarOpen: false }">
        <aside
            id="mobile-sidebar"
            class="fixed inset-y-0 left-0 z-40 w-72 lg:w-64 bg-navy-900 text-white flex flex-col shadow-2xl lg:shadow-none -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out lg:static"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            @click.outside="sidebarOpen = false"
            x-cloak>
            <x-sidebar />
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-30 bg-white border-b border-slate-200">
                <x-topbar />
            </header>

            <div class="lg:hidden">
                <div
                    x-cloak
                    x-show="sidebarOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-30 bg-navy-950/60"
                    @click="sidebarOpen = false"
                    aria-hidden="true"></div>
            </div>

            <main class="flex-1 w-full max-w-[100rem] mx-auto p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <x-alert type="success" dismissible>{{ session('status') }}</x-alert>
                @endif

                @if (session('temporary_password'))
                    <x-alert type="warning" dismissible>
                        <strong>{{ settings('system_short_name', 'TSTS') }} one-time notice</strong><br>
                        Temporary password for <strong>{{ session('temporary_email') }}</strong>:<br>
                        <code class="font-mono text-sm bg-white/60 px-2 py-0.5 rounded">{{ session('temporary_password') }}</code><br>
                        It will not be shown again.
                    </x-alert>
                @endif

                @yield('content')
            </main>

            <footer class="border-t border-slate-200 bg-white/60 px-6 py-3 text-center text-xs text-slate-500">
                {{ settings('system_name', config('app.name')) }}
            </footer>
        </div>
    </div>

    @yield('modals')
    @stack('scripts')
</body>
</html>