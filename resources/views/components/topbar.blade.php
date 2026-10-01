<div class="h-16 px-4 sm:px-6 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3 min-w-0">
        {{-- Persistent brand trigger: always visible, even while the drawer is
             hidden, and it toggles the navigation. A real button so keyboard
             users get the aria-expanded state and a visible focus ring. --}}
        <button
            id="sidebar-brand-toggle"
            type="button"
            class="shrink-0 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-navy-500 focus-visible:ring-offset-2"
            @click="$store.sidebar.toggleDrawer()"
            :aria-expanded="$store.sidebar.open.toString()"
            aria-controls="mobile-sidebar"
            :aria-label="$store.sidebar.open ? 'Hide navigation menu' : 'Show navigation menu'">
            <img
                src="{{ asset('images/dccp-logo.png') }}"
                alt="{{ $organizationName }} logo"
                class="w-9 h-9 object-contain">
        </button>

        <x-search-box />
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        <a
            href="{{ route('tickets.create') }}"
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-navy-700 text-white text-sm font-medium hover:bg-navy-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Ticket
        </a>

        <x-notifications-bell />

        <x-theme-toggle />

        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                class="flex items-center gap-2 p-1.5 rounded-full hover:bg-surface-sunken"
                @click="open = !open"
                aria-haspopup="menu"
                aria-expanded="open">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-navy-700 text-white text-xs font-semibold">{{ auth()->user()->initials }}</span>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.opacity
                class="absolute right-0 mt-2 w-56 rounded-xl bg-surface ring-1 ring-line shadow-lg py-1 z-40">
                <div class="px-4 py-2 border-b border-line-soft">
                    <p class="text-sm font-medium text-ink truncate">{{ auth()->user()->full_name }}</p>
                    <p class="text-xs text-ink-subtle truncate">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-ink hover:bg-surface-muted">Profile</a>
                <a href="{{ route('profile.change-password') }}" class="block px-4 py-2 text-sm text-ink hover:bg-surface-muted">Change password</a>
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">Log out</button>
                </form>
            </div>
        </div>
    </div>
</div>