@extends('layouts.app')

@section('title', 'Priorities')

@section('content')
    <x-page-header
        title="Priorities"
        description="Priorities set the SLA deadline for new tickets. Higher levels are resolved sooner."
        breadcrumb="Priorities" />

    <x-form.errors />

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <x-card title="Add priority">
            <form method="POST" action="{{ route('admin.priorities.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="key" required>Key</x-form.label>
                        <x-form.input name="key" required value="{{ old('key') }}" placeholder="e.g. normal" />
                    </div>
                    <div>
                        <x-form.label for="name" required>Name</x-form.label>
                        <x-form.input name="name" required value="{{ old('name') }}" placeholder="e.g. Normal" />
                    </div>
                </div>
                <div>
                    <x-form.label for="description">Description</x-form.label>
                    <x-form.textarea name="description" rows="3" value="{{ old('description') }}" placeholder="e.g. Standard requests that should be handled within two business days." />
                    <p class="mt-1 text-xs text-ink-subtle">Shown to requesters and staff when they choose this priority.</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="sla_hours" required>SLA (hours)</x-form.label>
                        <x-form.input name="sla_hours" type="number" min="1" max="8760" required value="{{ old('sla_hours') }}" />
                        <p class="mt-1 text-xs text-ink-subtle">Target response time in working hours.</p>
                    </div>
                    <div>
                        <x-form.label for="level" required>Level (higher = more urgent)</x-form.label>
                        <x-form.input name="level" type="number" min="1" max="10" required value="{{ old('level') }}" placeholder="e.g. 5" />
                        <p class="mt-1 text-xs text-ink-subtle">1 is the least urgent, 10 the most. Each level must be unique.</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <input type="hidden" name="is_requester_selectable" value="0">
                    <x-form.checkbox name="is_requester_selectable" label="Requesters can select this" :checked="old('is_requester_selectable', false)" />
                    <input type="hidden" name="is_active" value="0">
                    <x-form.checkbox name="is_active" label="Active" :checked="old('is_active', true)" />
                </div>
                <x-button.primary type="submit" class="w-full justify-center">Add priority</x-button.primary>
            </form>
        </x-card>

        <div class="lg:col-span-2 space-y-6">
            <x-card flush>
                <div class="p-4 sm:p-5 border-b border-line-soft">
                    <x-list-search :route="route('admin.priorities.index')" placeholder="Search by name or key" />
                </div>
                <div class="px-4 sm:px-5 py-3 pt-4">
                    <x-button.primary class="w-full">Apply Filters</x-button.primary>
                </div>

                @if ($priorities->isEmpty())
                    <x-empty-state title="No priorities found" description="No priorities match your search." />
                @else
                    <p class="px-4 sm:px-5 py-2.5 text-xs text-ink-subtle border-b border-line-soft">
                        Showing <span class="font-medium text-ink">{{ $priorities->firstItem() }}&ndash;{{ $priorities->lastItem() }}</span>
                        of <span class="font-medium text-ink">{{ $priorities->total() }}</span>
                        {{ Str::plural('priority', $priorities->total()) }}
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-subtle border-b border-line bg-surface-muted/80">
                                    <th class="px-4 py-2.5">Priority</th>
                                    <th class="px-4 py-2.5">Key</th>
                                    <th class="px-4 py-2.5 hidden md:table-cell">SLA</th>
                                    <th class="px-4 py-2.5 hidden md:table-cell">Level</th>
                                    <th class="px-4 py-2.5 hidden lg:table-cell">Requester selectable</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-surface divide-y divide-line-soft">
                                @foreach ($priorities as $priority)
                                    <tr class="hover:bg-surface-muted transition-colors">
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <x-priority-badge :priority="$priority" />
                                                @if ($priority->is_system)
                                                    <x-badge color="slate">System</x-badge>
                                                @endif
                                            </div>
                                            @if ($priority->description)
                                                <div class="text-xs text-ink-subtle max-w-xs truncate mt-0.5">{{ $priority->description }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-sm font-mono text-ink-muted">{{ $priority->key }}</td>
                                        <td class="px-4 py-2.5 text-sm text-ink-muted hidden md:table-cell">{{ $priority->sla_hours }} hours</td>
                                        <td class="px-4 py-2.5 text-sm text-ink-muted hidden md:table-cell">{{ $priority->level }}</td>
                                        <td class="px-4 py-2.5 hidden lg:table-cell">
                                            @if ($priority->is_requester_selectable)
                                                <x-badge color="green">Yes</x-badge>
                                            @else
                                                <x-badge color="slate">No</x-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            @if ($priority->is_active)
                                                <x-badge color="green">Active</x-badge>
                                            @else
                                                <x-badge color="slate">Inactive</x-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center justify-end gap-2">
                                                <x-button.secondary type="button" class="px-3 py-1.5 text-xs" @click="$store.modals.open('edit-priority-{{ $priority->id }}')">Edit</x-button.secondary>
                                                <form method="POST" action="{{ route('admin.priorities.toggle-active', $priority) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-ink-muted ring-1 ring-line-strong hover:bg-surface-muted">
                                                        {{ $priority->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($priorities->hasPages())
                        <div class="px-5 py-4 border-t border-line-soft">
                            {{ $priorities->links() }}
                        </div>
                    @endif
                @endif
            </x-card>
        </div>
    </div>
@endsection

@section('modals')
    @foreach ($priorities as $priority)
        <x-modal id="edit-priority-{{ $priority->id }}" title="Edit priority: {{ $priority->name }}">
            <form method="POST" action="{{ route('admin.priorities.update', $priority) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="key" required>Key</x-form.label>
                        <x-form.input name="key" required value="{{ $priority->key }}" :readonly="$priority->is_system" />
                    </div>
                    <div>
                        <x-form.label for="name" required>Name</x-form.label>
                        <x-form.input name="name" required value="{{ $priority->name }}" />
                    </div>
                </div>
                <div>
                    <x-form.label for="description">Description</x-form.label>
                    <x-form.textarea name="description" rows="3" value="{{ $priority->description }}" placeholder="e.g. Standard requests that should be handled within two business days." />
                    <p class="mt-1 text-xs text-ink-subtle">Shown to requesters and staff when they choose this priority.</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="sla_hours" required>SLA (hours)</x-form.label>
                        <x-form.input name="sla_hours" type="number" min="1" max="8760" required value="{{ $priority->sla_hours }}" />
                        <p class="mt-1 text-xs text-ink-subtle">Target response time in working hours.</p>
                    </div>
                    <div>
                        <x-form.label for="level" required>Level (higher = more urgent)</x-form.label>
                        <x-form.input name="level" type="number" min="1" max="10" required value="{{ $priority->level }}" placeholder="e.g. 5" />
                        <p class="mt-1 text-xs text-ink-subtle">1 is the least urgent, 10 the most. Each level must be unique.</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <input type="hidden" name="is_requester_selectable" value="0">
                    <x-form.checkbox name="is_requester_selectable" label="Requesters can select this" :checked="$priority->is_requester_selectable" />
                    <input type="hidden" name="is_active" value="0">
                    <x-form.checkbox name="is_active" label="Active" :checked="$priority->is_active" />
                </div>
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-ink-muted hover:bg-surface-sunken" @click="$store.modals.close('edit-priority-{{ $priority->id }}')">Cancel</button>
                    <x-button.primary type="submit">Save changes</x-button.primary>
                </div>
            </form>
        </x-modal>
    @endforeach
@endsection