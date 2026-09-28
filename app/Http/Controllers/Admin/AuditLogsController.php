<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogsController extends Controller
{
    /**
     * The activity monitor. Append-only feed of logins, registrations,
     * verifications, ticket events and system changes.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = $this->applyFilters(AuditLog::query()->with('user'), $request)
            ->latest('id')
            ->limit(50)
            ->get();

        $feedUrl = route('admin.audit-logs.feed', $request->query());

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'feedUrl' => $feedUrl,
            'users' => User::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'models' => AuditLog::query()->whereNotNull('auditable_type')->distinct()->pluck('auditable_type'),
        ]);
    }

    /**
     * HTML fragment of the latest rows, re-rendered by the page every 15 seconds.
     */
    public function feed(Request $request): \Illuminate\Contracts\View\View
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = $this->applyFilters(AuditLog::query()->with('user'), $request)
            ->latest('id')
            ->limit(30)
            ->get();

        return view('admin.audit-logs.partials.rows', ['logs' => $logs]);
    }

    /**
     * CSV export with formula-injection protection. Read-only export.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = $this->applyFilters(AuditLog::query()->with('user'), $request)
            ->latest('id')
            ->limit(50000);

        $filename = 'activity-monitor-'.now()->format('Y-m-d-Hi').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the export with the correct encoding.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Timestamp', 'User', 'Action', 'Description', 'IP address', 'User agent',
            ]);

            foreach ($query->cursor() as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $this->csvSafe($log->user?->full_name ?? 'System'),
                    $this->csvSafe($log->action),
                    $this->csvSafe($log->description),
                    $this->csvSafe($log->ip_address),
                    $this->csvSafe($log->user_agent),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('user'), fn (Builder $q) => $q->where('user_id', $request->query('user')))
            ->when($request->filled('action'), fn (Builder $q) => $q->where('action', $request->query('action')))
            ->when($request->filled('model'), fn (Builder $q) => $q->where('auditable_type', $request->query('model')))
            ->when($request->filled('ticket'), function (Builder $q) use ($request) {
                $term = trim($request->string('ticket'));

                $ticketIds = Ticket::query()
                    ->whereLike('ticket_number', $term)
                    ->pluck('id');

                $q->where('auditable_type', Ticket::class)->whereIn('auditable_id', $ticketIds);
            })
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->when($request->filled('ip'), function (Builder $q) use ($request) {
                $term = trim($request->string('ip'));

                $q->whereLike('ip_address', $term);
            });
    }

    /**
     * Neutralize spreadsheet formula injection in exported cells.
     */
    protected function csvSafe(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return $value;
        }

        $trimmed = ltrim($value);

        if ($trimmed === '') {
            return $value;
        }

        if (in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r", "\n", '|'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
