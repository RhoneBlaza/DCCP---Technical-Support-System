@extends('layouts.app')

@section('title', 'Verification review')

@php
    $isOpen = $request->isOpen();
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
        title="Verification review"
        description="Registration by {{ $request->user->email }}"
        breadcrumb="{{ $request->id }}" />

    <x-form.errors />

    @if ($duplicateId)
        <x-alert type="warning" class="mt-6">
            This ID number (<strong>{{ $request->id_number }}</strong>) matches another user or another verification
            record in the system. Review carefully before approving.
        </x-alert>
    @endif

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="ID document" description="Visible to administrators only. Every view is recorded in the audit log.">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wide text-ink-faint">ID type</span>
                        <p class="text-sm text-ink font-medium">{{ $request->id_type_label }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wide text-ink-faint">ID number</span>
                        <p class="text-sm font-mono text-ink font-medium">{{ $request->id_number }}</p>
                    </div>
                    <div class="ml-auto">
                        <x-button.secondary :href="route('admin.verifications.image', $request)" target="_blank">
                            View ID document
                        </x-button.secondary>
                    </div>
                </div>
            </x-card>

            <x-card title="Submission history" description="The full verification history is kept, resubmissions create new records." flush>
                <div class="divide-y divide-line-soft">
                    @foreach ($history as $entry)
                        <div class="px-4 sm:px-5 py-3.5 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-mono text-ink-faint">{{ $entry->submitted_at->format('M j, Y H:i') }}</span>
                            <x-badge color="{{ $statusColors[$entry->status] ?? 'gray' }}">{{ $statusLabels[$entry->status] ?? $entry->status }}</x-badge>
                            <span class="text-xs text-ink-subtle">{{ $entry->id_type_label }} · {{ $entry->id_number }}</span>
                            @if ($entry->decision_note)
                                <span class="w-full text-xs text-ink-muted mt-1">{{ $entry->decision_note }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Applicant">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Name</dt>
                        <dd class="text-ink font-medium">{{ $request->user->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Email</dt>
                        <dd class="text-ink">{{ $request->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Department</dt>
                        <dd class="text-ink">{{ $request->user->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Position</dt>
                        <dd class="text-ink">{{ $request->user->position ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Contact</dt>
                        <dd class="text-ink">{{ $request->user->contact_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Account</dt>
                        <dd class="text-ink">{{ $request->user->account_status->label() }}</dd>
                    </div>
                </dl>
            </x-card>

            @if ($isOpen)
                <x-card title="Decision" description="Rejecting or requesting resubmission requires a reason.">
                    <div x-data="{ tab: 'approve' }" class="space-y-4">
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="tab = 'approve'" :class="tab === 'approve' ? 'bg-navy-700 text-white' : 'bg-surface-sunken text-ink-muted hover:bg-surface-active'" class="px-2 py-2 rounded-lg text-xs font-semibold transition-colors">Approve</button>
                            <button type="button" @click="tab = 'reject'" :class="tab === 'reject' ? 'bg-red-600 text-white' : 'bg-surface-sunken text-ink-muted hover:bg-surface-active'" class="px-2 py-2 rounded-lg text-xs font-semibold transition-colors">Reject</button>
                            <button type="button" @click="tab = 'resubmit'" :class="tab === 'resubmit' ? 'bg-orange-500 text-white' : 'bg-surface-sunken text-ink-muted hover:bg-surface-active'" class="px-2 py-2 rounded-lg text-xs font-semibold transition-colors">Resubmit</button>
                        </div>

                        <form method="POST" action="{{ route('admin.verifications.approve', $request) }}" x-show="tab === 'approve'">
                            @csrf
                            @method('PATCH')
                            <div class="rounded-lg bg-surface-muted border border-line p-3 text-xs text-ink-muted mb-3">
                                Approving activates the account immediately. The requester will be notified in-app and by email if mail is configured.
                            </div>
                            <x-button.primary type="submit" class="w-full justify-center">Approve registration</x-button.primary>
                        </form>

                        <form method="POST" action="{{ route('admin.verifications.reject', $request) }}" x-show="tab === 'reject'" x-cloak>
                            @csrf
                            @method('PATCH')
                            <x-form.label for="decision_note" required>Reason for rejection</x-form.label>
                            <x-form.textarea name="decision_note" rows="3" required placeholder="Explain why the registration cannot be approved. This is shared with the requester." />
                            <x-button.danger type="submit" class="w-full justify-center mt-3">Reject registration</x-button.danger>
                        </form>

                        <form method="POST" action="{{ route('admin.verifications.resubmit', $request) }}" x-show="tab === 'resubmit'" x-cloak>
                            @csrf
                            @method('PATCH')
                            <x-form.label for="decision_note" required>What to correct</x-form.label>
                            <x-form.textarea name="decision_note" rows="3" required placeholder="Tell the requester what to fix, e.g. upload a clearer copy of the ID. This is shared with the requester." />
                            <x-button.secondary type="submit" class="w-full justify-center mt-3">Request resubmission</x-button.secondary>
                        </form>
                    </div>
                </x-card>
            @else
                <x-card title="Decision">
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Status</dt>
                            <dd><x-badge color="{{ $statusColors[$request->status] ?? 'gray' }}">{{ $statusLabels[$request->status] ?? $request->status }}</x-badge></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Reviewed by</dt>
                            <dd class="text-ink">{{ $request->reviewer?->full_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Reviewed at</dt>
                            <dd class="text-ink">{{ $request->reviewed_at?->format('M j, Y H:i') ?? '—' }}</dd>
                        </div>
                        @if ($request->decision_note)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Note</dt>
                                <dd class="text-ink">{{ $request->decision_note }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>
            @endif
        </div>
    </div>
@endsection