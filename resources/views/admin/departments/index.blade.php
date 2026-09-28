@extends('layouts.app')

@section('title', 'Departments')

@section('content')
    <x-page-header
        title="Departments"
        description="Configure the departments that requesters belong to for reporting and routing."
        breadcrumb="Departments" />

    <x-form.errors />

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <x-card title="Add department">
            <form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-4">
                @csrf
                <div>
                    <x-form.label for="name" required>Name</x-form.label>
                    <x-form.input name="name" required value="{{ old('name') }}" placeholder="e.g. Registrar's Office" />
                </div>
                <div>
                    <x-form.label for="code" required>Code</x-form.label>
                    <x-form.input name="code" required value="{{ old('code') }}" placeholder="e.g. REGISTRAR" />
                </div>
                <div>
                    <x-form.label for="description">Description</x-form.label>
                    <x-form.textarea name="description" rows="3" value="{{ old('description') }}" />
                </div>
                <div>
                    <input type="hidden" name="is_active" value="0">
                    <x-form.checkbox name="is_active" label="Active" :checked="old('is_active', true)" />
                </div>
                <x-button.primary type="submit" class="w-full justify-center">Add department</x-button.primary>
            </form>
        </x-card>

        <div class="lg:col-span-2 space-y-6">
            <x-card flush>
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <x-list-search :route="route('admin.departments.index')" placeholder="Search by name or code" />
                </div>

                @if ($departments->isEmpty())
                    <x-empty-state title="No departments found" description="No departments match your search." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200 bg-slate-50/80">
                                    <th class="px-4 py-2.5">Name</th>
                                    <th class="px-4 py-2.5">Code</th>
                                    <th class="px-4 py-2.5 hidden md:table-cell">Users</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @foreach ($departments as $department)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-2.5">
                                            <div class="text-sm font-medium text-slate-800">{{ $department->name }}</div>
                                            @if ($department->description)
                                                <div class="text-xs text-slate-500 max-w-xs truncate">{{ $department->description }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-sm font-mono text-slate-600">{{ $department->code }}</td>
                                        <td class="px-4 py-2.5 text-sm text-slate-600 hidden md:table-cell">{{ $department->users_count }}</td>
                                        <td class="px-4 py-2.5">
                                            @if ($department->is_active)
                                                <x-badge color="green">Active</x-badge>
                                            @else
                                                <x-badge color="slate">Inactive</x-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center justify-end gap-2">
                                                <x-button.secondary type="button" class="px-3 py-1.5 text-xs" @click="$store.modals.open('edit-department-{{ $department->id }}')">Edit</x-button.secondary>
                                                <form method="POST" action="{{ route('admin.departments.toggle-active', $department) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50">
                                                        {{ $department->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($departments->hasPages())
                        <div class="px-5 py-4 border-t border-slate-100">
                            {{ $departments->links() }}
                        </div>
                    @endif
                @endif
            </x-card>
        </div>
    </div>
@endsection

@section('modals')
    @foreach ($departments as $department)
        <x-modal id="edit-department-{{ $department->id }}" title="Edit department: {{ $department->name }}">
            <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <x-form.label for="name" required>Name</x-form.label>
                    <x-form.input name="name" required value="{{ $department->name }}" />
                </div>
                <div>
                    <x-form.label for="code" required>Code</x-form.label>
                    <x-form.input name="code" required value="{{ $department->code }}" />
                </div>
                <div>
                    <x-form.label for="description">Description</x-form.label>
                    <x-form.textarea name="description" rows="3" value="{{ $department->description }}" />
                </div>
                <div>
                    <input type="hidden" name="is_active" value="0">
                    <x-form.checkbox name="is_active" label="Active" :checked="$department->is_active" />
                </div>
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100" @click="$store.modals.close('edit-department-{{ $department->id }}')">Cancel</button>
                    <x-button.primary type="submit">Save changes</x-button.primary>
                </div>
            </form>
        </x-modal>
    @endforeach
@endsection