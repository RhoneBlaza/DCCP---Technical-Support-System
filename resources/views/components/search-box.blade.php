<form action="{{ route('search.index') }}" method="GET" class="hidden md:block" role="search">
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search tickets, requesters…"
            class="w-72 lg:w-80 rounded-lg border border-line-strong bg-surface-muted pl-9 pr-3 py-2 text-sm text-ink placeholder:text-ink-faint focus:bg-surface focus:ring-2 focus:ring-navy-500 dark:focus:ring-navy-400 focus:border-navy-500"
            aria-label="Global search">
    </div>
</form>