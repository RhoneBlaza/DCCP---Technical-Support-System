@props(['filters', 'route' => null])

@php
    $isStaff = auth()->check() && auth()->user()->isStaff();
    $active = collect(['q', 'status', 'priority', 'category', 'department'])
        ->filter(fn ($key) => request()->filled($key))
        ->values();

    $label = function (string $key) use ($filters): string {
        return match ($key) {
            'q' => 'Search: “'.Str::limit(request('q'), 24).'”',
            'status' => 'Status: '.($filters['statuses']->firstWhere('key', request('status'))?->name ?? request('status')),
            'priority' => 'Priority: '.($filters['priorities']->firstWhere('key', request('priority'))?->name ?? request('priority')),
            'category' => 'Category: '.($filters['categories']->firstWhere('id', (int) request('category'))?->name ?? request('category')),
            'department' => 'Department: '.($filters['departments']->firstWhere('id', (int) request('department'))?->name ?? request('department')),
            default => '',
        };
    };
@endphp

<div class="space-y-3">
    <x-list-search
        :route="$route ?? request()->url()"
        name="q"
        placeholder="Search tickets, requesters…"
        :reset-keys="['status', 'priority', 'category', 'department', 'start_date', 'end_date']">
        <select name="status" title="Applied automatically" onchange="this.form.requestSubmit()" class="w-40 rounded-lg border border-line-strong bg-surface-muted px-2.5 py-2 text-sm text-ink focus:ring-2 focus:ring-navy-200 dark:focus:ring-navy-500/40 focus:border-navy-500">
            <option value="">Any status</option>
            @foreach ($filters['statuses'] as $status)
                <option value="{{ $status->key }}" @selected(request('status') === $status->key)>{{ $status->name }}</option>
            @endforeach
        </select>

        @if ($isStaff)
            <select name="priority" title="Applied automatically" onchange="this.form.requestSubmit()" class="w-36 rounded-lg border border-line-strong bg-surface-muted px-2.5 py-2 text-sm text-ink focus:ring-2 focus:ring-navy-200 dark:focus:ring-navy-500/40 focus:border-navy-500">
                <option value="">Any priority</option>
                @foreach ($filters['priorities'] as $priority)
                    <option value="{{ $priority->key }}" @selected(request('priority') === $priority->key)>{{ $priority->name }}</option>
                @endforeach
            </select>

            <select name="category" title="Applied automatically" onchange="this.form.requestSubmit()" class="w-44 rounded-lg border border-line-strong bg-surface-muted px-2.5 py-2 text-sm text-ink focus:ring-2 focus:ring-navy-200 dark:focus:ring-navy-500/40 focus:border-navy-500">
                <option value="">Any category</option>
                @foreach ($filters['categories'] as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            <select name="department" title="Applied automatically" onchange="this.form.requestSubmit()" class="w-44 rounded-lg border border-line-strong bg-surface-muted px-2.5 py-2 text-sm text-ink focus:ring-2 focus:ring-navy-200 dark:focus:ring-navy-500/40 focus:border-navy-500">
                <option value="">Any department</option>
                @foreach ($filters['departments'] as $department)
                    <option value="{{ $department->id }}" @selected(request('department') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        @endif

        <!-- Date filters: both ends apply on change and are kept in order so a
             range can never be submitted with the start after the end. -->
        <div class="flex-1 md:w-64">
            <x-form.label for="start_date">Start date</x-form.label>
            <input
                type="date"
                name="start_date"
                value="{{ request('start_date') }}"
                @if (request('end_date')) max="{{ request('end_date') }}" @endif
                onchange="if (this.value && this.form.end_date.value && this.value > this.form.end_date.value) { this.form.end_date.value = this.value; } this.form.requestSubmit();"
                class="block w-full rounded-lg border border-line-strong bg-surface-muted px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-navy-200 dark:focus:ring-navy-500/40 focus:border-navy-500">
        </div>

        <div class="flex-1 md:w-64">
            <x-form.label for="end_date">End date</x-form.label>
            <input
                type="date"
                name="end_date"
                value="{{ request('end_date') }}"
                @if (request('start_date')) min="{{ request('start_date') }}" @endif
                onchange="if (this.value && this.form.start_date.value && this.value < this.form.start_date.value) { this.form.start_date.value = this.value; } this.form.requestSubmit();"
                class="block w-full rounded-lg border border-line-strong bg-surface-muted px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-navy-200 dark:focus:ring-navy-500/40 focus:border-navy-500">
        </div>
    </x-list-search>

    @if ($active->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-ink-subtle">Active filters:</span>
            @foreach ($active as $key)
                <a
                    href="{{ request()->fullUrlWithoutQuery([$key, 'page']) }}"
                    class="inline-flex items-center gap-1 rounded-full bg-navy-50 dark:bg-navy-500/10 ring-1 ring-navy-100 dark:ring-navy-500/30 px-2.5 py-1 text-xs font-medium text-navy-800 dark:text-navy-100 hover:bg-navy-100 dark:hover:bg-navy-500/20"
                    title="Remove this filter">
                    <span>{{ $label($key) }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-navy-500 dark:text-navy-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endforeach
        </div>
    @endif
</div>