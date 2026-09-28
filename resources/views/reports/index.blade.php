@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <x-page-header
        title="Reports"
        description="Ticket activity and resolution statistics for the selected period."
        breadcrumb="Reports">
        <x-slot:actions>
            <x-button.secondary :href="route('reports.export', request()->query())">
                Export {{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }}
            </x-button.secondary>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mt-6" flush>
        <div class="p-4 sm:p-5 border-b border-slate-100">
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <x-form.label for="from">From</x-form.label>
                    <x-form.input name="from" type="date" :value="$from->format('Y-m-d')" />
                </div>
                <div>
                    <x-form.label for="to">To</x-form.label>
                    <x-form.input name="to" type="date" :value="$to->format('Y-m-d')" />
                </div>
                <div>
                    <x-form.label for="status">Status</x-form.label>
                    <x-form.select name="status" :options="$statuses->mapWithKeys(fn ($s) => [$s->key => $s->name])" :selected="request('status')" placeholder="All statuses" />
                </div>
                <div>
                    <x-form.label for="department">Department</x-form.label>
                    <x-form.select name="department" :options="$departments->mapWithKeys(fn ($d) => [$d->id => $d->name])" :selected="request('department')" placeholder="All departments" />
                </div>
                <x-button.primary type="submit">Apply</x-button.primary>
                @if (request()->filled('status') || request()->filled('department') || request('from') || request('to'))
                    <x-button.secondary :href="route('reports.index')">Reset</x-button.secondary>
                @endif
            </form>

            @if ($dateRangeSwapped)
                <p class="mt-3 text-sm text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded-lg px-3 py-2">
                    The "From" date was after the "To" date, so the period was read as
                    {{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }}.
                </p>
            @endif
        </div>
    </x-card>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <x-stat-card label="Tickets created" :value="$total" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 3h6m-6 4h6m-6 4h2" />
        <x-stat-card label="Resolved in period" :value="$resolved" color="green" icon="M5 13l4 4L19 7" />
        <x-stat-card label="Closed in period" :value="$closed" color="blue" icon="M6 18L18 6M6 6l12 12" />
        <x-stat-card label="Resolution rate" :value="$resolutionRate.'%'" color="amber" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </div>

    @php
        $breakdowns = [
            ['title' => 'By current status', 'data' => $byStatus, 'empty' => 'No tickets in this period.'],
            ['title' => 'By department', 'data' => $byDepartment, 'empty' => 'No tickets in this period.'],
            ['title' => 'By priority', 'data' => $byPriority, 'empty' => 'No tickets in this period.'],
        ];
        $maxCol = fn ($data) => max($data->values()->max() ?: 0, 1);
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        @foreach ($breakdowns as $breakdown)
            <x-card :title="$breakdown['title']" flush>
                @if ($total === 0)
                    <x-empty-state title="Nothing to show" :description="$breakdown['empty']" />
                @else
                    <dl class="divide-y divide-slate-100">
                        @foreach ($breakdown['data'] as $label => $count)
                            <div class="px-4 sm:px-5 py-3">
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <dt class="truncate {{ $count === 0 ? 'text-slate-400' : 'text-slate-700' }}">{{ $label }}</dt>
                                    <dd class="ml-2 font-semibold {{ $count === 0 ? 'text-slate-400' : 'text-slate-800' }}">{{ $count }}</dd>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-navy-600" style="width: {{ round(($count / $maxCol($breakdown['data'])) * 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </x-card>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <x-card title="Resolution times" description="Based on tickets resolved within the selected period">
            @if ($resolution['resolved_count'] === 0)
                <x-empty-state title="No resolved tickets" description="Resolution time statistics will appear once tickets are resolved." />
            @else
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div class="rounded-lg bg-slate-50 ring-1 ring-slate-100 p-4">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Resolved</dt>
                        <dd class="text-xl font-bold text-slate-800 mt-1">{{ $resolution['resolved_count'] }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 ring-1 ring-slate-100 p-4">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Average</dt>
                        <dd class="text-xl font-bold text-slate-800 mt-1">{{ $resolution['avg_hours'] }}<span class="text-sm font-medium text-slate-400">h</span></dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 ring-1 ring-slate-100 p-4">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Fastest</dt>
                        <dd class="text-xl font-bold text-green-600 mt-1">{{ $resolution['fastest_hours'] }}<span class="text-sm font-medium text-slate-400">h</span></dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 ring-1 ring-slate-100 p-4">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Slowest</dt>
                        <dd class="text-xl font-bold text-red-600 mt-1">{{ $resolution['slowest_hours'] }}<span class="text-sm font-medium text-slate-400">h</span></dd>
                    </div>
                </dl>
            @endif
        </x-card>

        <x-card title="Top requesters" description="Requesters with the most tickets in this period" flush>
            @if ($topRequesters->isEmpty())
                <x-empty-state title="No tickets" description="Requester statistics will appear once tickets are created." />
            @else
                <ol class="divide-y divide-slate-100">
                    @foreach ($topRequesters as $entry)
                        <li class="flex items-center gap-3 px-4 sm:px-5 py-3">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-navy-100 text-navy-800 text-xs font-semibold shrink-0">{{ $entry->requester?->initials ?? '?' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $entry->requester?->full_name ?? 'Unknown' }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $entry->requester?->department?->name ?? '' }}</p>
                            </div>
                            <x-badge color="blue">{{ $entry->total }} ticket{{ $entry->total === 1 ? '' : 's' }}</x-badge>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>
@endsection