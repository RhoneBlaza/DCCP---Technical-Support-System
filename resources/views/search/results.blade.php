@extends('layouts.app')

@section('title', 'Search results')

@section('content')
    <x-page-header
        title="Search results"
        description="{{ $term === '' ? 'Enter a search term to find tickets.' : 'Results for “'.$term.'”' }}"
        breadcrumb="Search" />

    <x-card flush class="mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100">
            <form method="GET" action="{{ route('search.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ $term }}"
                        placeholder="Search by ticket number, subject, description, asset or requester…"
                        autofocus
                        class="w-full rounded-lg border border-slate-300 bg-slate-50 pl-9 pr-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-navy-200 focus:border-navy-500">
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-navy-700 text-white text-sm font-medium hover:bg-navy-800">
                    Search
                </button>
            </form>
        </div>

        @if ($term === '')
            <x-empty-state title="Waiting for a search term" description="Ticket numbers, subjects, descriptions, asset numbers and requester names are searched." />
        @else
            <x-tickets.table
                :tickets="$tickets"
                empty-title="No matching tickets"
                empty-description="Nothing matched your search. Try a different keyword or ticket number." />
            @if ($tickets->count() === 50)
                <div class="px-5 py-4 border-t border-slate-100 text-xs text-slate-500">
                    Showing the first 50 matches. Narrow your search to find more specific results.
                </div>
            @endif
        @endif
    </x-card>
@endsection