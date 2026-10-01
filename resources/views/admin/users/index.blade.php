@extends('layouts.app')

@section('title', 'User accounts')

@section('content')
    <x-page-header
        title="User accounts"
        description="Manage staff accounts. Pending registrations are shown first waiting for your approval.">
        <x-slot:actions>
            <x-button.primary :href="route('admin.users.create')">Create user</x-button.primary>
        </x-slot:actions>
    </x-page-header>

    <x-card flush class="mt-6">
        <div class="p-4 sm:p-5 border-b border-line-soft">
            <x-list-search :route="route('admin.users.index')" placeholder="Search by name, email or employee ID" :reset-keys="['status']">
                <div class="w-44">
                    <x-form.select
                        name="status"
                        title="Applied automatically"
                        onchange="this.form.requestSubmit()"
                        :options="[
                            '' => 'All statuses',
                            'pending' => 'Pending approval',
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ]"
                        :selected="request('status')" />
                </div>
            </x-list-search>
        </div>

        @if ($users->isEmpty())
            <x-empty-state title="No users found" description="No user accounts match your search." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-subtle border-b border-line bg-surface-muted/80">
                            <th class="px-4 py-2.5">Name</th>
                            <th class="px-4 py-2.5">Employee ID</th>
                            <th class="px-4 py-2.5">Email</th>
                            <th class="px-4 py-2.5">Department</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-line-soft">
                        @foreach ($users as $user)
                            <tr class="hover:bg-surface-muted transition-colors">
                                <td class="px-4 py-2.5">
                                    <div class="text-sm font-medium text-ink">
                                        <a href="{{ route('admin.users.show', $user) }}">{{ $user->full_name }}</a>
                                    </div>
                                    <div class="text-xs text-ink-subtle">{{ $user->role->label() }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-sm text-ink-muted">{{ $user->employee_id }}</td>
                                <td class="px-4 py-2.5 text-sm text-ink-muted">{{ $user->email }}</td>
                                <td class="px-4 py-2.5 text-sm text-ink-muted">{{ $user->department?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($user->account_status === \App\Enums\AccountStatus::Pending)
                                        <x-badge color="yellow">Pending approval</x-badge>
                                    @elseif ($user->account_status === \App\Enums\AccountStatus::Suspended)
                                        <x-badge color="red">Suspended</x-badge>
                                    @elseif ($user->is_active)
                                        <x-badge color="green">Active</x-badge>
                                    @else
                                        <x-badge color="slate">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($user->account_status === \App\Enums\AccountStatus::Pending)
                                            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-button.primary type="submit">Approve</x-button.primary>
                                            </form>

                                            <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-button.danger type="submit">Reject</x-button.danger>
                                            </form>
                                        @else
                                            <div
                                                class="flex flex-col items-end gap-2"
                                                x-data="{ open: false }"
                                                @click.outside="open = false">
                                                <div class="flex items-center gap-2">
                                                    <x-button.secondary :href="route('admin.users.edit', $user)">Edit</x-button.secondary>
                                                    <x-button.secondary
                                                        @click="open = ! open"
                                                        aria-haspopup="true"
                                                        aria-expanded="open">More actions</x-button.secondary>
                                                </div>

                                                {{-- Expanded in flow on purpose: an absolutely positioned panel is
                                                     clipped by the table's `overflow-x-auto` scroll box and
                                                     overlaps the rows underneath it. --}}
                                                <div
                                                    x-cloak
                                                    x-show="open"
                                                    x-transition.opacity
                                                    class="w-56 rounded-lg bg-surface ring-1 ring-line py-1 shadow-lg">
                                                    <form method="POST" action="{{ route('admin.users.toggle-suspend', $user) }}" class="block" @submit="open = false">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-ink hover:bg-surface-muted">
                                                            {{ $user->account_status === \App\Enums\AccountStatus::Suspended ? 'Reactivate' : 'Suspend' }}
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="block" @submit="open = false">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="px-5 py-4 border-t border-line-soft">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
