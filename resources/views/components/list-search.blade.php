@props([
    'route' => null,
    'name' => 'search',
    'placeholder' => 'Search',
    'resetKeys' => [],
])

@php
    $action = $route ?? request()->url();
    $hasFilters = collect($resetKeys)
        ->push($name)
        ->contains(fn ($key) => request()->filled($key));
@endphp

{{--
    The one search pattern shared by every list page, so filtering behaves the
    same everywhere: there is no separate "Filter" button. Typed text is applied
    with the in-field magnifier or Enter, and any dropdowns passed through the
    slot submit themselves the moment they change.
--}}
<form method="GET" action="{{ $action }}" class="flex flex-wrap items-center gap-3" role="search">
    <div class="relative min-w-56 flex-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <x-form.input
            :name="$name"
            type="search"
            :placeholder="$placeholder"
            :value="request($name)"
            :aria-label="$placeholder"
            class="pl-9 pr-10" />
        <button
            type="submit"
            title="Search"
            class="absolute right-1 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-7 h-7 rounded-md text-slate-500 hover:bg-slate-200 hover:text-slate-800 focus-visible:ring-2 focus-visible:ring-navy-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="sr-only">Search</span>
        </button>
    </div>

    {{ $slot }}

    @if (! $slot->isEmpty())
        <span class="hidden xl:inline text-xs text-slate-400">Dropdowns apply automatically</span>
    @endif

    @if ($hasFilters)
        <a href="{{ $action }}" class="text-sm font-medium text-navy-700 hover:underline">Reset</a>
    @endif
</form>
