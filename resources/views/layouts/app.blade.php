<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $systemName) — {{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dccp-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/dccp-logo.png') }}">
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full">
    {{-- h-[100dvh] + overflow-hidden keeps the footer pinned to the viewport and
         scrolls only <main>, so the footer can never slide out of reach. The
         whole shell is one Alpine component so the drawer's aside, hover zone
         and click-catcher are all walked on start(); without a shared x-data
         root the aside's x-cloak would hide it forever. --}}
    <div
        class="flex h-[100vh] h-[100dvh] overflow-hidden overflow-x-hidden"
        x-data
        @keydown.escape.window="$store.sidebar.closeDrawer()">

        {{-- Invisible left-edge hover zone (desktop pointers only). It opens the
             drawer on hover and stops short of the footer so the footer stays
             clickable. z-20 keeps it above the page but below the sticky header,
             so it never covers the brand button. --}}
        <div
            class="hidden lg:block fixed left-0 top-0 bottom-12 z-20 w-4"
            x-show="! $store.sidebar.open"
            @mouseenter="$store.sidebar.openDrawer()"
            aria-hidden="true"></div>

        {{-- Auto-hide navigation. Fixed and translated so it slides in over the
             page instead of pushing it, which means opening it never reflows
             the content. --}}
        <aside
            id="mobile-sidebar"
            x-cloak
            class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] flex-col bg-navy-900 text-white shadow-2xl transition-transform duration-200 ease-in-out"
            :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
            :inert="! $store.sidebar.open"
            @mouseenter="$store.sidebar.cancelClose()"
            @mouseleave="$store.sidebar.scheduleClose()">
            <x-sidebar />
        </aside>

        {{-- Clear backdrop on small screens; a transparent click target on large
             screens. Tapping or clicking it dismisses the drawer. It sits below
             the header (z-20 < z-30) so the brand button stays usable. --}}
        <div
            x-cloak
            x-show="$store.sidebar.open"
            x-transition.opacity
            class="fixed inset-0 z-20 bg-navy-950/60 lg:bg-transparent"
            @click="$store.sidebar.closeDrawer()"
            aria-hidden="true"></div>

        <div class="flex-1 flex flex-col h-full min-w-0">
            <header class="sticky top-0 z-30 bg-surface border-b border-line flex-shrink-0">
                <x-topbar />
            </header>

            <main class="flex-1 w-full max-w-[100rem] mx-auto p-4 sm:p-6 lg:p-8 overflow-y-auto min-h-0">
                @if (session('status'))
                    <x-alert type="success" dismissible>{{ session('status') }}</x-alert>
                @endif

                @if (session('temporary_password'))
                    <x-alert type="warning" dismissible>
                        <strong>{{ $systemShortName }} one-time notice</strong><br>
                        Temporary password for <strong>{{ session('temporary_email') }}</strong>:<br>
                        <code class="font-mono text-sm bg-surface/60 px-2 py-0.5 rounded">{{ session('temporary_password') }}</code><br>
                        It will not be shown again.
                    </x-alert>
                @endif

                @yield('content')
            </main>

            <footer class="border-t border-line bg-surface px-4 sm:px-6 py-3 text-xs text-ink-subtle flex-shrink-0">
                <div class="max-w-[100rem] mx-auto w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <p class="truncate">{{ $systemName }}</p>
                    <p class="truncate">{{ $organizationName }}</p>
                </div>
            </footer>
        </div>
    </div>

    @yield('modals')
    @stack('scripts')
</body>
</html>
