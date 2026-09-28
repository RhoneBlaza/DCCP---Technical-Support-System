@extends('layouts.app')

@section('title', 'All tickets')

@section('content')
    <x-page-header title="All tickets" description="Every ticket in the system" breadcrumb="All tickets">
    </x-page-header>

    <x-card class="mt-6" flush>
        <div class="p-4 sm:p-5 border-b border-slate-100">
            <x-tickets.filters :filters="$filters" :route="route('tickets.index')" />
        </div>
        <x-tickets.table :tickets="$tickets" empty-description="No tickets match your filters." />
        @if ($tickets->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                <span class="block mb-2 text-xs font-medium text-slate-500">Page navigation</span>
                {{ $tickets->links() }}
            </div>
        @endif
    </x-card>
@endsection