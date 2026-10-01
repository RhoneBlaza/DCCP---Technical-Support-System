@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <x-page-header
        title="Categories"
        description="Group issues into a two-level tree. Requesters pick a leaf category when submitting a ticket."
        breadcrumb="Categories" />

    <x-form.errors />

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <x-card title="Add category">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <x-form.label for="parent_id">Parent category</x-form.label>
                    <x-form.select name="parent_id" :options="$roots->pluck('name', 'id')" :selected="old('parent_id')" placeholder="Top-level category" />
                </div>
                <div>
                    <x-form.label for="name" required>Name</x-form.label>
                    <x-form.input name="name" required value="{{ old('name') }}" placeholder="e.g. Printer / Scanner" />
                </div>
                <div>
                    <x-form.label for="description">Description</x-form.label>
                    <x-form.textarea name="description" rows="3" value="{{ old('description') }}" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="sort_order">Sort order</x-form.label>
                        <x-form.input name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}" />
                    </div>
                    <div class="flex items-end pb-1.5">
                        <input type="hidden" name="is_active" value="0">
                        <x-form.checkbox name="is_active" label="Active" :checked="old('is_active', true)" />
                    </div>
                </div>
                <x-button.primary type="submit" class="w-full justify-center">Add category</x-button.primary>
            </form>
        </x-card>

        <div class="lg:col-span-2 space-y-6">
            <x-card flush>
                <div class="p-4 sm:p-5 border-b border-line-soft">
                    <x-list-search :route="route('admin.categories.index')" placeholder="Search categories" />
                </div>

                @if ($roots->isEmpty())
                    <x-empty-state title="No categories found" description="No categories match your search." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-subtle border-b border-line bg-surface-muted/80">
                                    <th class="px-4 py-2.5">Name</th>
                                    <th class="px-4 py-2.5">Type</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-surface divide-y divide-line-soft">
                                @foreach ($roots as $category)
                                    <tr class="hover:bg-surface-muted transition-colors">
                                        <td class="px-4 py-2.5">
                                            <div class="text-sm font-semibold text-ink">{{ $category->name }}</div>
                                            @if ($category->description)
                                                <div class="text-xs text-ink-subtle max-w-xs truncate">{{ $category->description }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <x-badge color="indigo">Parent</x-badge>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            @if ($category->is_active)
                                                <x-badge color="green">Active</x-badge>
                                            @else
                                                <x-badge color="slate">Inactive</x-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center justify-end gap-2">
                                                <x-button.secondary type="button" class="px-3 py-1.5 text-xs" @click="$store.modals.open('edit-category-{{ $category->id }}')">Edit</x-button.secondary>
                                                <form method="POST" action="{{ route('admin.categories.toggle-active', $category) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-ink-muted ring-1 ring-line-strong hover:bg-surface-muted">
                                                        {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-red-300 dark:ring-red-500/40 hover:bg-red-50 dark:hover:bg-red-500/10">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach ($category->children as $child)
                                        <tr class="hover:bg-surface-muted transition-colors">
                                            <td class="px-4 py-2.5 pl-8">
                                                <div class="flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
                                                    <span class="text-sm text-ink">{{ $child->name }}</span>
                                                    @if ($child->description)
                                                        <span class="text-xs text-ink-faint truncate max-w-[16rem]">{{ $child->description }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <x-badge color="slate">Child</x-badge>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @if ($child->is_active)
                                                    <x-badge color="green">Active</x-badge>
                                                @else
                                                    <x-badge color="slate">Inactive</x-badge>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <div class="flex items-center justify-end gap-2">
                                                    <x-button.secondary type="button" class="px-3 py-1.5 text-xs" @click="$store.modals.open('edit-category-{{ $child->id }}')">Edit</x-button.secondary>
                                                    <form method="POST" action="{{ route('admin.categories.toggle-active', $child) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-ink-muted ring-1 ring-line-strong hover:bg-surface-muted">
                                                            {{ $child->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.categories.destroy', $child) }}" onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-red-300 dark:ring-red-500/40 hover:bg-red-50 dark:hover:bg-red-500/10">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($roots->hasPages())
                        <div class="px-5 py-4 border-t border-line-soft">
                            {{ $roots->links() }}
                        </div>
                    @endif
                @endif
            </x-card>
        </div>
    </div>
@endsection

@section('modals')
    @foreach ($roots as $category)
        <x-modal id="edit-category-{{ $category->id }}" title="Edit category: {{ $category->name }}">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <x-form.label for="parent_id">Parent category</x-form.label>
                    <x-form.select name="parent_id" :options="$roots->where('id', '!=', $category->id)->pluck('name', 'id')" :selected="$category->parent_id" placeholder="Top-level category" />
                </div>
                <div>
                    <x-form.label for="name" required>Name</x-form.label>
                    <x-form.input name="name" required value="{{ $category->name }}" />
                </div>
                <div>
                    <x-form.label for="description">Description</x-form.label>
                    <x-form.textarea name="description" rows="3" value="{{ $category->description }}" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="sort_order">Sort order</x-form.label>
                        <x-form.input name="sort_order" type="number" min="0" value="{{ $category->sort_order }}" />
                    </div>
                    <div class="flex items-end pb-1.5">
                        <input type="hidden" name="is_active" value="0">
                        <x-form.checkbox name="is_active" label="Active" :checked="$category->is_active" />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-ink-muted hover:bg-surface-sunken" @click="$store.modals.close('edit-category-{{ $category->id }}')">Cancel</button>
                    <x-button.primary type="submit">Save changes</x-button.primary>
                </div>
            </form>
        </x-modal>
        @foreach ($category->children as $child)
            <x-modal id="edit-category-{{ $child->id }}" title="Edit category: {{ $child->name }}">
                <form method="POST" action="{{ route('admin.categories.update', $child) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-form.label for="parent_id">Parent category</x-form.label>
                        <x-form.select name="parent_id" :options="$roots->where('id', '!=', $child->id)->pluck('name', 'id')" :selected="$child->parent_id" placeholder="Top-level category" />
                    </div>
                    <div>
                        <x-form.label for="name" required>Name</x-form.label>
                        <x-form.input name="name" required value="{{ $child->name }}" />
                    </div>
                    <div>
                        <x-form.label for="description">Description</x-form.label>
                        <x-form.textarea name="description" rows="3" value="{{ $child->description }}" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-form.label for="sort_order">Sort order</x-form.label>
                            <x-form.input name="sort_order" type="number" min="0" value="{{ $child->sort_order }}" />
                        </div>
                        <div class="flex items-end pb-1.5">
                            <input type="hidden" name="is_active" value="0">
                            <x-form.checkbox name="is_active" label="Active" :checked="$child->is_active" />
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-ink-muted hover:bg-surface-sunken" @click="$store.modals.close('edit-category-{{ $child->id }}')">Cancel</button>
                        <x-button.primary type="submit">Save changes</x-button.primary>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endforeach
@endsection