@php
    $user = auth()->user();
    $sections = [];

    if ($user->isAdmin()) {
        $sections['General'] = [
            ['dashboard', 'Dashboard', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['tickets.index', 'Tickets', 'M20 13V8a5 5 0 00-5-5H8a4 4 0 00-4 4v12a2 2 0 002 2h2'],
            ['admin.users.index', 'Users', 'M17 20h5v-2a3 3 0 00-5.36-1.86M12 20h9M9 20H4v-2a3 3 0 015.36-1.86M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['admin.verifications.index', 'Verifications', 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a1 1 0 011-1h2a1 1 0 011 1v1m-4 0a3 3 0 106 0m-6 0h6'],
        ];
        $sections['Configuration'] = [
            ['admin.departments.index', 'Departments', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m9-8h-2m2-4h-2m-5 8V9m3 0V5'],
            ['admin.categories.index', 'Categories', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
            ['admin.priorities.index', 'Priorities', 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['admin.statuses.index', 'Ticket Statuses', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ];
        $sections['Reports'] = [
            ['reports.index', 'Reports', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['admin.audit-logs.index', 'Activity Monitor', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ];
        $sections['System'] = [
            ['admin.settings.index', 'Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z'],
        ];
    } elseif ($user->isSupport()) {
        $sections['General'] = [
            ['dashboard', 'Dashboard', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['support.queue', 'Support Queue', 'M4 7v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V7c0-.55-.45-1-1-1H5c-.55 0-1 .45-1 1zm11 0a3 3 0 00-6 0'],
            ['support.my-tickets', 'My Tickets', 'M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'],
            ['support.all-tickets', 'All Tickets', 'M20 13V8a5 5 0 00-5-5H8a4 4 0 00-4 4v12a2 2 0 002 2h2'],
        ];
        $sections['Reports'] = [
            ['reports.index', 'Reports', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ];
    } else {
        $sections['General'] = [
            ['dashboard', 'Dashboard', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['tickets.my-tickets', 'My Tickets', 'M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'],
            ['tickets.create', 'Submit Ticket', 'M12 4v16m8-8H4'],
        ];
        $sections['Account'] = [
            ['notifications.index', 'Notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
            ['profile.show', 'Profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ];
    }
@endphp

{{-- The header grows with the organization name instead of clipping it, and
     break-words keeps a long unbroken name from spilling past the sidebar. --}}
<div class="flex items-center gap-3 px-5 py-2 border-b border-white/10 shrink-0">
    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-white ring-1 ring-white/20 shrink-0 overflow-hidden">
        <img
            src="{{ asset('images/dccp-logo.png') }}"
            alt="{{ settings('organization_name', 'Data Center College of the Philippines - Bangued') }} logo"
            class="w-8 h-8 object-contain">
    </div>
    <div class="min-w-0">
        <p class="text-sm font-semibold leading-tight">{{ settings('system_short_name', 'TSTS') }}</p>
        @php
            $organizationName = settings('organization_name', '');
        @endphp
        <p class="text-[11px] text-slate-300 leading-tight break-words">{{ $organizationName }}</p>
    </div>
</div>

<nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5" aria-label="Main navigation">
    @foreach ($sections as $label => $items)
        <div>
            <p class="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ $label }}</p>
            <div class="space-y-0.5">
                @foreach ($items as [$name, $itemLabel, $icon])
                    @if (Route::has($name))
                        @php
                            $isActive = request()->routeIs($name.'*');
                        @endphp
                        <a
                            href="{{ route($name) }}"
                            class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-navy-100 hover:bg-white/5 hover:text-white' }}"
                            @if ($isActive) aria-current="page" @endif>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 {{ $isActive ? 'text-navy-200' : 'opacity-70 group-hover:opacity-100' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            <span class="truncate">{{ $itemLabel }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</nav>

<a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-4 py-3 border-t border-white/10 hover:bg-white/5 transition-colors">
    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-navy-500/30 ring-1 ring-white/20 text-white text-xs font-semibold shrink-0">{{ $user->initials }}</span>
    <span class="min-w-0">
        <span class="block text-sm font-medium text-white truncate">{{ $user->full_name }}</span>
        <span class="block text-xs text-slate-300 truncate">{{ $user->role->label() }}</span>
    </span>
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-auto text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
</a>