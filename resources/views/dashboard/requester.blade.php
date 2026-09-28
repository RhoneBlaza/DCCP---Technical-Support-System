@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="My Dashboard" description="Your technical support requests">
        <x-slot:actions>
            <x-button.primary :href="route('tickets.create')">Submit a ticket</x-button.primary>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <x-stat-card label="Open" :value="$stats['open']" color="blue" icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
        <x-stat-card label="In progress" :value="$stats['in_progress']" color="amber" icon="M13 10V3L4 14h7v7l9-11h-7z" />
        <x-stat-card label="Awaiting action from me" :value="$stats['pending']" color="slate" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        <x-stat-card label="Resolved" :value="$stats['resolved']" color="green" icon="M5 13l4 4L19 7" />
        <x-stat-card label="Closed" :value="$stats['closed']" color="navy" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </div>

    <x-card class="mt-6" title="Recent tickets">
        @php
            $recent = auth()->user()->tickets()->with('status', 'priority')->latest()->limit(5)->get();
        @endphp
        @if ($recent->isEmpty())
            <x-empty-state title="No tickets yet" description="Submit your first support request when you encounter an issue." :action-href="route('tickets.create')" action-label="Submit a ticket" />
        @else
            <x-tickets.table :tickets="$recent" :show-requester="false" />
            <div class="mt-4">
                <x-button.secondary :href="route('tickets.my-tickets')">View all my tickets</x-button.secondary>
            </div>
        @endif
    </x-card>
@endsection