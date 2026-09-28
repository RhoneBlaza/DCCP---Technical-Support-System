@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Support Dashboard" description="Your workload and the institution-wide queue">
        <x-slot:actions>
            <x-button.primary :href="route('support.queue')">Open support queue</x-button.primary>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <x-stat-card label="My assigned (open)" :value="$stats['my_assigned']" color="navy" icon="M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
        <x-stat-card label="Unassigned in queue" :value="$stats['unassigned']" color="blue" icon="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        <x-stat-card label="Overdue SLA" :value="$stats['overdue']" color="red" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        <x-stat-card label="Resolved (7 days)" :value="$stats['recently_resolved']" color="green" icon="M5 13l4 4L19 7" />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        <x-card title="Unassigned tickets" description="Open tickets waiting for a support staff assignment">
            @if ($unassigned_tickets->isNotEmpty())
                <x-tickets.table :tickets="$unassigned_tickets" />
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <x-button.secondary :href="route('support.queue')">Open support queue</x-button.secondary>
                </div>
            @else
                <x-empty-state title="No unassigned tickets" description="The queue is clear. Nice work!" />
            @endif
        </x-card>

        <x-card title="My assigned tickets" description="Tickets currently assigned to you">
            @if ($my_assigned_tickets->isNotEmpty())
                <x-tickets.table :tickets="$my_assigned_tickets" />
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <x-button.secondary :href="route('support.my-tickets')">View all my tickets</x-button.secondary>
                </div>
            @else
                <x-empty-state title="No assigned tickets" description="You have no open tickets assigned to you right now." />
            @endif
        </x-card>

        <x-card title="Overdue tickets" description="Tickets past their SLA due date" class="xl:col-span-2">
            @if ($overdue_tickets->isNotEmpty())
                <x-tickets.table :tickets="$overdue_tickets" />
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <x-button.secondary :href="route('support.queue')">Review the queue</x-button.secondary>
                </div>
            @else
                <x-empty-state title="No overdue tickets" description="Everything is within SLA. Nice work!" />
            @endif
        </x-card>
    </div>
@endsection