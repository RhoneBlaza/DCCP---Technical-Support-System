@extends('layouts.app')

@section('title', 'My tickets')

@section('content')
    <x-page-header title="My tickets" description="{{ auth()->user()->isStaff() ? 'Tickets assigned to you' : 'Support requests you submitted' }}" breadcrumb="My tickets">
    </x-page-header>

    <x-card class="mt-6" flush>
        <div class="p-4 sm:p-5 border-b border-line-soft">
            <x-tickets.filters :filters="$filters" :route="route('tickets.my-tickets')" />
        </div>
        <x-tickets.table :tickets="$tickets" :show-requester="auth()->user()->isStaff()" empty-description="No tickets match your filters." />
        @if ($tickets->hasPages())
            <div class="px-5 py-4 border-t border-line-soft">
                {{ $tickets->links() }}
            </div>
        @endif
    </x-card>
@endsection