@extends('layouts.app')

@section('title', 'Ticket statuses')

@section('content')
    <x-page-header
        title="Ticket statuses"
        description="Statuses drive the workflow and SLA pausing. Pending-type statuses pause the SLA deadline."
        breadcrumb="Ticket Statuses" />

    <x-form.errors />

    @php
        $typeOptions = collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()]);
        $colorOptions = collect($colors)->mapWithKeys(fn ($c) => [$c => ucfirst($c)]);
    @endphp

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <x-card title="Add status">
            <form method="POST" action="{{ route('admin.statuses.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="name" required>Name</x-form.label>
                        <x-form.input name="name" required value="{{ old('name') }}" placeholder="e.g. Waiting on vendor" />
                    </div>
                    <div>
                        <x-form.label for="key">Key</x-form.label>
                        <x-form.input name="key" value="{{ old('key') }}" placeholder="auto from name" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="type" required>Type</x-form.label>
                        <x-form.select name="type" :options="$typeOptions" :selected="old('type')" placeholder="Select type" />
                    </div>
                    <div>
                        <x-form.label for="color" required>Badge color</x-form.label>
                        <x-form.select name="color" :options="$colorOptions" :selected="old('color', 'gray')" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="sort_order" required hint="Display rank, not a percentage. Lower numbers appear first and each rank must be unique.">Sort order</x-form.label>
                        <x-form.input name="sort_order" type="number" min="1" max="100" required value="{{ old('sort_order', 1) }}" />
                    </div>
                    <div class="flex items-end pb-1.5">
                        <input type="hidden" name="is_active" value="0">
                        <x-form.checkbox name="is_active" label="Active" :checked="old('is_active', true)" />
                    </div>
                </div>
                <x-button.primary type="submit" class="w-full justify-center">Add status</x-button.primary>
            </form>
        </x-card>

        <div class="lg:col-span-2 space-y-6">
            <x-card flush>
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <x-list-search :route="route('admin.statuses.index')" placeholder="Search by name or key" />
                </div>
                <div class="px-4 sm:px-5 py-3">
                    <x-button.primary class="w-full">Apply Filters</x-button.primary>
                </div>

                @if ($statuses->isEmpty())
                    <x-empty-state title="No statuses found" description="No statuses match your search." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200 bg-slate-50/80">
                                    <th class="px-4 py-2.5">Name</th>
                                    <th class="px-4 py-2.5">Key</th>
                                    <th class="px-4 py-2.5">Type</th>
                                    <th class="px-4 py-2.5 hidden sm:table-cell" title="Lower numbers appear first in the status list. Each status must have a unique sort order.">Sort order</th>
                                    <th class="px-4 py-2.5 hidden md:table-cell">Pauses SLA</th>
                                    <th class="px-4 py-2.5">Active</th>
                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @foreach ($statuses as $status)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <x-badge :color="$status->color">{{ $status->name }}</x-badge>
                                                @if ($status->is_system)
                                                    <x-badge color="slate">System</x-badge>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 text-sm font-mono text-slate-600">{{ $status->key }}</td>
                                        <td class="px-4 py-2.5 text-sm text-slate-600">{{ $status->type->label() }}</td>
                                        <td class="px-4 py-2.5 text-sm text-slate-600 hidden sm:table-cell">{{ $status->sort_order }}</td>
                                        <td class="px-4 py-2.5 hidden md:table-cell">
                                            @if ($status->pauses_sla)
                                                <x-badge color="yellow">Pauses</x-badge>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            @if ($status->is_active)
                                                <x-badge color="green">Active</x-badge>
                                            @else
                                                <x-badge color="slate">Inactive</x-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center justify-end gap-2">
                                                <x-button.secondary type="button" class="px-3 py-1.5 text-xs" @click="$store.modals.open('edit-status-{{ $status->id }}')">Edit</x-button.secondary>
                                                @php
                                                    $deactivationBlocker = $status->deactivationBlocker();
                                                @endphp
                                                <form method="POST" action="{{ route('admin.statuses.toggle-active', $status) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        type="submit"
                                                        @disabled($deactivationBlocker !== null)
                                                        title="{{ $deactivationBlocker ?? ($status->is_active ? 'Deactivate this status' : 'Activate this status') }}"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-medium ring-1 ring-slate-300 text-slate-600 hover:bg-slate-50 disabled:text-slate-400 disabled:bg-slate-100 disabled:cursor-not-allowed disabled:ring-slate-200">
                                                        {{ $status->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($statuses->hasPages())
                        <div class="px-5 py-4 border-t border-slate-100">
                            {{ $statuses->links() }}
                        </div>
                    @endif
                @endif
            </x-card>
        </div>
    </div>
@endsection

@section('modals')
    @foreach ($statuses as $status)
        <x-modal id="edit-status-{{ $status->id }}" title="Edit status: {{ $status->name }}">
            <form method="POST" action="{{ route('admin.statuses.update', $status) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="name" required>Name</x-form.label>
                        <x-form.input name="name" required value="{{ $status->name }}" />
                    </div>
                    <div>
                        <x-form.label for="key">Key</x-form.label>
                        <x-form.input name="key" value="{{ $status->key }}" :disabled="$status->is_system" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="type" required>Type</x-form.label>
                        <x-form.select name="type" :options="$typeOptions" :selected="$status->type->value" />
                    </div>
                    <div>
                        <x-form.label for="color" required>Badge color</x-form.label>
                        <x-form.select name="color" :options="$colorOptions" :selected="$status->color" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="sort_order" required hint="Display rank, not a percentage. Lower numbers appear first and each rank must be unique.">Sort order</x-form.label>
                        <x-form.input name="sort_order" type="number" min="1" max="100" required value="{{ $status->sort_order }}" />
                    </div>
                    <div class="flex items-end pb-1.5">
                        <input type="hidden" name="is_active" value="0">
                        <x-form.checkbox name="is_active" label="Active" :checked="$status->is_active" :disabled="$status->is_essential" />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100" @click="$store.modals.close('edit-status-{{ $status->id }}')">Cancel</button>
                    <x-button.primary type="submit">Save changes</x-button.primary>
                </div>
            </form>
        </x-modal>
    @endforeach
@endsection