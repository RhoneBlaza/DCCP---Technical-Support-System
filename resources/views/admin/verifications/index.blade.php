@extends('layouts.app')

@section('title', 'ID verifications')

@php
    $statusLabels = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'resubmit_requested' => 'Resubmission requested',
    ];
    $statusColors = [
        'pending' => 'blue',
        'approved' => 'green',
        'rejected' => 'red',
        'resubmit_requested' => 'orange',
    ];
@endphp

@section('content')
    <x-page-header
        title="ID verifications"
        description="Review and approve registration requests. ID documents are only visible to administrators." />

    <x-card flush class="mt-6">
        <div class="p-4 sm:p-5 border-b border-slate-100">
            <x-list-search :route="route('admin.verifications.index')" placeholder="Search by name, email or ID number" :reset-keys="['status']">
                <div class="w-44">
                    <x-form.select
                        name="status"
                        title="Applied automatically"
                        onchange="this.form.requestSubmit()"
                        :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => $statusLabels[$s]])->prepend('All statuses', '')->sort()"
                        :selected="request('status')" />
                </div>
            </x-list-search>
        </div>

        @if ($requests->isEmpty())
            <x-empty-state title="No verification requests" description="Registrations submitted through the public sign-up form will appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200 bg-slate-50/80">
                            <th class="px-4 py-2.5">Applicant</th>
                            <th class="px-4 py-2.5">ID type</th>
                            <th class="px-4 py-2.5">ID number</th>
                            <th class="px-4 py-2.5">Submitted</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach ($requests as $verification)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2.5">
                                    <div class="text-sm font-medium text-slate-800">{{ $verification->user->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $verification->user->email }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-sm text-slate-600">{{ $verification->id_type_label }}</td>
                                <td class="px-4 py-2.5 text-sm font-mono text-slate-600">{{ $verification->id_number }}</td>
                                <td class="px-4 py-2.5 text-sm text-slate-600">{{ $verification->submitted_at->format('M j, Y H:i') }}</td>
                                <td class="px-4 py-2.5">
                                    <x-badge color="{{ $statusColors[$verification->status] ?? 'gray' }}">{{ $statusLabels[$verification->status] ?? $verification->status }}</x-badge>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-end">
                                        <x-button.secondary :href="route('admin.verifications.show', $verification)">Review</x-button.secondary>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $requests->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection