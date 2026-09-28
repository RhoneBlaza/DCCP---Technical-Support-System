<div class="h-16 px-4 sm:px-6 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3 min-w-0">
        <a href="{{ route('dashboard') }}" class="shrink-0" aria-label="{{ settings('organization_name', 'Data Center College of the Philippines - Bangued') }}">
            <img
                src="{{ asset('images/dccp-logo.png') }}"
                alt="{{ settings('organization_name', 'Data Center College of the Philippines - Bangued') }} logo"
                class="w-9 h-9 object-contain">
        </a>

        <button
            id="sidebar-toggle"
            type="button"
            class="lg:hidden p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100"
            @click="sidebarOpen = true"
            aria-label="Open navigation menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
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

        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                class="flex items-center gap-2 p-1.5 rounded-full hover:bg-slate-100"
                @click="open = !open"
                aria-haspopup="menu"
                aria-expanded="open">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-navy-700 text-white text-xs font-semibold">{{ auth()->user()->initials }}</span>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.opacity
                class="absolute right-0 mt-2 w-56 rounded-xl bg-white ring-1 ring-slate-200 shadow-lg py-1 z-40">
                <div class="px-4 py-2 border-b border-slate-100">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ auth()->user()->full_name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                <a href="{{ route('profile.change-password') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Change password</a>
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Log out</button>
                </form>
            </div>
        </div>
    </div>
</div>