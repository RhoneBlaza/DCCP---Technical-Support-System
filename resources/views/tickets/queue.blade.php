@extends('layouts.app')

@section('title', 'Support queue')

@section('content')
    <x-page-header title="Support queue" description="Unresolved tickets ordered by SLA due date, unassigned first" breadcrumb="Support queue">
    </x-page-header>

    <x-card class="mt-6" flush>
        <div class="p-4 sm:p-5 border-b border-line-soft">
            <x-tickets.filters :filters="$filters" :route="route('support.queue')" />
        </div>
        <x-tickets.table :tickets="$tickets" empty-description="The queue is clear. Nice work!" />
        @if ($tickets->hasPages())
            <div class="px-5 py-4 border-t border-line-soft">
                {{ $tickets->links() }}
            </div>
        @endif
    </x-card>
@endsection