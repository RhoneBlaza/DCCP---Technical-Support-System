@props(['tickets', 'showRequester' => true, 'emptyTitle' => 'No tickets found', 'emptyDescription' => null])

@php
    $requesterMode = auth()->check() && ! auth()->user()->isStaff();
    $showRequester = $showRequester && ! $requesterMode;
    $paginator = $tickets instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $tickets : null;
@endphp

@if ($paginator)
    <p class="px-4 sm:px-5 py-2.5 text-xs text-slate-500 border-b border-slate-100">
        @if ($paginator->total() > 0)
            Showing <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
            {{ Str::plural('ticket', $paginator->total()) }}
        @else
            No tickets to show
        @endif
    </p>
@endif

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide border-b border-slate-200 bg-slate-50/80">
                <th class="px-4 py-2.5 rounded-tl-lg">Ticket</th>
                @if (! $requesterMode && $showRequester)
                    <th class="px-4 py-2.5 hidden md:table-cell">Requester</th>
                @endif
                @if (! $requesterMode)
                    <th class="px-4 py-2.5 hidden lg:table-cell">Category / Department</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5 hidden sm:table-cell">Priority</th>
                @else
                    <th class="px-4 py-2.5">Status</th>
                @endif
                <th class="px-4 py-2.5 hidden sm:table-cell">Created</th>
                @if (! $requesterMode)
                    <th class="px-4 py-2.5 hidden sm:table-cell">Due / SLA</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($tickets as $ticket)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-2.5 align-top">
                        <a href="{{ route('tickets.show', $ticket) }}" class="font-mono text-xs text-navy-700 font-semibold hover:underline">{{ $ticket->ticket_number }}</a>
                        <p class="text-slate-800 font-medium leading-snug mt-0.5">
                            <a href="{{ route('tickets.show', $ticket) }}" class="hover:underline">{{ $ticket->display_subject }}</a>
                        </p>
                    </td>
                    @if (! $requesterMode && $showRequester)
                        <td class="px-4 py-2.5 align-top hidden md:table-cell">
                            <p class="text-slate-700">{{ $ticket->requester?->full_name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $ticket->requester?->department?->name ?? '—' }}</p>
                        </td>
                    @endif
                    @if (! $requesterMode)
                        <td class="px-4 py-2.5 align-top hidden lg:table-cell">
                            <p class="text-slate-600">{{ $ticket->category?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $ticket->department?->name ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-2.5 align-top">
                            <x-status-badge :status="$ticket->status" />
                        </td>
                        <td class="px-4 py-2.5 align-top hidden sm:table-cell">
                            <x-priority-badge :priority="$ticket->priority" />
                        </td>
                    @else
                        <td class="px-4 py-2.5 align-top">
                            <x-status-badge :status="$ticket->status" />
                        </td>
                    @endif
                    <td class="px-4 py-2.5 align-top hidden sm:table-cell text-slate-500" title="{{ $ticket->created_at }}">
                        <x-safe-date :date="$ticket->created_at" />
                    </td>
                    @if (! $requesterMode)
                        <td class="px-4 py-2.5 align-top hidden sm:table-cell">
                            <x-sla-badge :ticket="$ticket" />
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="10">
                        <x-empty-state :title="$emptyTitle" :description="$emptyDescription" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>