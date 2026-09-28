<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatusType;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        if (! auth()->user()->isStaff()) {
            abort(403, 'Reports are only available to support staff.');
        }

        [$from, $to, $dateRangeSwapped] = $this->dateRange($request);

        $base = $this->createdInPeriod($request, $from, $to);

        $total = $base->clone()->count();
        $resolved = $this->applyFilters($base->clone(), $request)
            ->whereHas('status', fn ($q) => $q->where('type', TicketStatusType::Resolved->value))
            ->count();
        $closed = $this->applyFilters($base->clone(), $request)
            ->whereHas('status', fn ($q) => $q->where('type', TicketStatusType::Closed->value))
            ->count();

        $byStatus = $this->overview($request, $from, $to, 'status');
        $byDepartment = $this->overview($request, $from, $to, 'department');
        $byPriority = $this->overview($request, $from, $to, 'priority');

        $resolution = $this->resolutionStats($request, $from, $to);

        $topRequesters = $base->clone()
            ->with('requester')
            ->selectRaw('requester_id, count(*) as total')
            ->groupBy('requester_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'dateRangeSwapped' => $dateRangeSwapped,
            'total' => $total,
            'resolved' => $resolved,
            'closed' => $closed,
            'resolutionRate' => $total > 0 ? round(($resolved / $total) * 100) : 0,
            'byStatus' => $byStatus,
            'byDepartment' => $byDepartment,
            'byPriority' => $byPriority,
            'resolution' => $resolution,
            'topRequesters' => $topRequesters,
            'statuses' => TicketStatus::active()->ordered()->get(),
            'departments' => Department::active()->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! auth()->user()->isStaff()) {
            abort(403);
        }

        [$from, $to] = $this->dateRange($request);

        $tickets = $this->createdInPeriod($request, $from, $to)
            ->with(['requester', 'status', 'priority', 'department', 'category', 'assignedUser'])
            ->orderBy('ticket_number')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tickets-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Ticket No.', 'Subject', 'Category', 'Department', 'Priority', 'Status',
                'Requester', 'Assigned To', 'Created', 'Due (SLA)', 'Resolved At', 'Closed At', 'Reopens',
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($out, [
                    $this->csvSafe($ticket->ticket_number),
                    $this->csvSafe($ticket->subject),
                    $this->csvSafe($ticket->category?->name ?? ''),
                    $this->csvSafe($ticket->department?->name ?? ''),
                    $this->csvSafe($ticket->priority?->name ?? ''),
                    $this->csvSafe($ticket->status?->name ?? ''),
                    $this->csvSafe($ticket->requester?->full_name ?? ''),
                    $this->csvSafe($ticket->assignedUser?->full_name ?? ''),
                    $this->csvSafe($ticket->created_at?->format('Y-m-d H:i')),
                    $this->csvSafe($ticket->due_at?->format('Y-m-d H:i')),
                    $this->csvSafe($ticket->resolved_at?->format('Y-m-d H:i')),
                    $this->csvSafe($ticket->closed_at?->format('Y-m-d H:i')),
                    $ticket->reopen_count,
                ]);
            }

            fclose($out);
        }, 'tickets.csv', $headers);
    }

    /**
     * Neutralize spreadsheet formula injection in exported cells.
     */
    protected function csvSafe(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Resolve the reporting period. A reversed range is swapped rather than
     * silently returning an empty report, and unparsable input falls back to
     * the defaults instead of erroring.
     *
     * @return array{0: Carbon, 1: Carbon, 2: bool}
     */
    protected function dateRange(Request $request): array
    {
        $from = $this->parseDate($request->query('from'), now()->startOfMonth());
        $to = $this->parseDate($request->query('to'), now()->endOfDay());

        if ($from->greaterThan($to)) {
            return [$to->copy()->startOfDay(), $from->copy()->endOfDay(), true];
        }

        return [$from, $to, false];
    }

    protected function parseDate(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * Tickets created inside the period, narrowed by whatever filters are applied.
     */
    protected function createdInPeriod(Request $request, Carbon $from, Carbon $to): Builder
    {
        return Ticket::query()
            ->whereBetween('tickets.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->tap(fn (Builder $query) => $this->applyFilters($query, $request));
    }

    /**
     * The only place status and department filters are applied, so the summary
     * cards, the breakdowns and the export can never drift apart. A blank, zero
     * or non-numeric department means "all departments" rather than no results.
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $status = $this->filterValue($request, 'status');
        $department = $this->filterValue($request, 'department');

        return $query
            ->when($status, fn (Builder $q) => $q
                ->whereHas('status', fn ($s) => $s->where('key', $status)))
            ->when($department, fn (Builder $q) => $q
                ->where('tickets.department_id', (int) $department));
    }

    protected function filterValue(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if ($key === 'department') {
            return ctype_digit($value) && (int) $value > 0 ? $value : null;
        }

        return $value;
    }

    protected function overview(Request $request, Carbon $from, Carbon $to, string $dimension): Collection
    {
        $map = [
            'status' => ['ticket_statuses', 'status_id', 'name'],
            'department' => ['departments', 'department_id', 'name'],
            'priority' => ['priorities', 'priority_id', 'name'],
        ];

        [$table, $column, $label] = $map[$dimension];

        $counts = $this->createdInPeriod($request, $from, $to)
            ->join($table, 'tickets.'.$column, '=', $table.'.id')
            ->selectRaw($table.'.name as label, count(*) as total')
            ->groupBy($table.'.id', $table.'.name')
            ->pluck('total', 'label');

        return $this->dimensionLabels($dimension)
            ->mapWithKeys(fn (string $name): array => [$name => (int) $counts->get($name, 0)])
            ->sortByDesc(fn (int $count): int => $count);
    }

    /**
     * Every active label for a dimension, so a status nobody has used still shows
     * up as zero instead of silently vanishing from the report.
     */
    protected function dimensionLabels(string $dimension): Collection
    {
        return match ($dimension) {
            'status' => TicketStatus::active()->ordered()->pluck('name'),
            'department' => Department::active()->orderBy('name')->pluck('name'),
            'priority' => Priority::active()->orderBy('level')->pluck('name'),
        };
    }

    protected function resolutionStats(Request $request, Carbon $from, Carbon $to): array
    {
        $durations = Ticket::query()
            ->whereBetween('tickets.resolved_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->tap(fn (Builder $query) => $this->applyFilters($query, $request))
            ->whereNotNull('tickets.created_at')
            ->whereNotNull('tickets.resolved_at')
            ->get(['tickets.created_at', 'tickets.resolved_at'])
            ->map(fn ($ticket) => $ticket->created_at->diffInSeconds($ticket->resolved_at));

        $count = $durations->count();

        if ($count === 0) {
            return ['resolved_count' => 0, 'avg_hours' => null, 'fastest_hours' => null, 'slowest_hours' => null];
        }

        return [
            'resolved_count' => $count,
            'avg_hours' => round($durations->avg() / 3600, 1),
            'fastest_hours' => round($durations->min() / 3600, 1),
            'slowest_hours' => round($durations->max() / 3600, 1),
        ];
    }
}
