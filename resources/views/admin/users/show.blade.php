@extends('layouts.app')

@section('title', $user->full_name)

@section('content')
    <x-page-header
        title="User: {{ $user->full_name }}"
        description="Viewing details for {{ $user->full_name }}.">
        <x-slot:actions>
            <x-button.secondary :href="route('admin.users.edit', $user)">Edit</x-button.secondary>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                @csrf
                @method('DELETE')
                <x-button.danger type="submit">Delete</x-button.danger>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mt-6">
        <div class="p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between">
                <div class="w-full sm:w-1/2">
                    <div class="flex items-center space-x-4">
                        @if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path))
                            <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="Profile photo" class="h-12 w-12 rounded-full">
                        @else
                            <div class="h-12 w-12 rounded-full bg-surface-active flex items-center justify-center text-ink-subtle">
                                {{ strtoupper($user->initials) }}
                            </div>
                        @endif
                        <div>
                            <div class="text-xl font-semibold text-ink">{{ $user->full_name }}</div>
                            <div class="text-sm text-ink-subtle">{{ $user->employee_id }}</div>
                        </div>
                    </div>
                </div>
                <div class="w-full sm:w-1/2 mt-4 sm:mt-0 sm:text-right">
                    <div class="text-sm font-medium text-ink-muted">Account Status</div>
                    <div class="mt-1">
                        @if ($user->account_status === \App\Enums\AccountStatus::Pending)
                            <x-badge color="yellow">{{ $user->account_status->label() }}</x-badge>
                        @elseif ($user->account_status === \App\Enums\AccountStatus::Suspended)
                            <x-badge color="red">{{ $user->account_status->label() }}</x-badge>
                        @elseif ($user->is_active)
                            <x-badge color="green">{{ $user->account_status->label() }}</x-badge>
                        @else
                            <x-badge color="slate">{{ $user->account_status->label() }}</x-badge>
                        @endif
                    </div>
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div class="text-ink-subtle">Email</div>
                <dd class="text-ink">{{ $user->email }}</dd>

                <div class="text-ink-subtle">Department</div>
                <dd class="text-ink">{{ $user->department?->name ?? '—' }}</dd>

                <div class="text-ink-subtle">Position</div>
                <dd class="text-ink">{{ $user->position ?? '—' }}</dd>

                <div class="text-ink-subtle">Role</div>
                <dd class="text-ink">{{ $user->role->label() }}</dd>

                <div class="text-ink-subtle">Contact Number</div>
                <dd class="text-ink">{{ $user->contact_number ?? '—' }}</dd>

                <div class="text-ink-subtle">Must Change Password</div>
                <dd class="text-ink">{{ $user->must_change_password ? 'Yes' : 'No' }}</dd>

                <div class="text-ink-subtle">Last Login At</div>
                <dd class="text-ink">{{ $user->last_login_at ? $user->last_login_at->toFormattedDateString() : 'Never' }}</dd>

                <div class="text-ink-subtle">Created At</div>
                <dd class="text-ink">{{ $user->created_at->toFormattedDateString() }}</dd>
            </dl>
        </div>
    </x-card>

    @if ($user->assignedTickets->isNotEmpty())
        <x-card class="mt-6">
            <div class="p-4 sm:p-5 border-b border-line-soft">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-ink">Assigned Tickets</h2>
                </div>
            </div>
            <div class="p-4 sm:p-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-subtle border-b border-line bg-surface-muted/80">
                            <th class="px-4 py-2.5">Ticket Number</th>
                            <th class="px-4 py-2.5">Subject</th>
                            <th class="px-4 py-2.5">Priority</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-line-soft">
                        @foreach ($user->assignedTickets as $ticket)
                            <tr class="hover:bg-surface-muted transition-colors">
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-ink">{{ $ticket->ticket_number }}</a>
                                </td>
                                <td class="px-4 py-2.5">{{ $ticket->subject }}</td>
                                <td class="px-4 py-2.5">{{ $ticket->priority?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($ticket->status)
                                        <x-badge color="{{ strtolower($ticket->status->type->value) }}">{{ $ticket->status->name }}</x-badge>
                                    @else
                                        <span class="text-ink-subtle">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">{{ $ticket->created_at->toFormattedDateString() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @else
        <div class="mt-6">
            <x-empty-state title="No Assigned Tickets" description="This user has no tickets currently assigned to them." />
        </div>
    @endif
@endsection