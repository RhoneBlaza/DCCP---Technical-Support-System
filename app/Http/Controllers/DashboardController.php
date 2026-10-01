<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Hex equivalents of the badge palette so charts share the same status colors.
     */
    protected const STATUS_COLORS = [
        'gray' => '#64748b',
        'blue' => '#3b82f6',
        'indigo' => '#6366f1',
        'teal' => '#14b8a6',
        'yellow' => '#eab308',
        'orange' => '#f97316',
        'green' => '#22c55e',
        'red' => '#ef4444',
        'rose' => '#f43f5e',
        'purple' => '#a855f7',
    ];

    public function index(): View
    {
        $user = auth()->user();

        return match ($user->role) {
            UserRole::Admin => $this->admin(),
            UserRole::Support => $this->support(),
            UserRole::Requester => $this->requester(),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Admin dashboard
    |--------------------------------------------------------------------------
    */

    protected function admin(): View
    {
        $base = fn () => Ticket::query();

        /*
         * Every card here counts one status key rather than a status type, so
         * each card matches the matching slice of the "Tickets by status" donut.
         */
        $stats = [
            'total' => $base()->count(),
            'open' => $base()->whereHas('status', fn ($q) => $q->where('key', 'open'))->count(),
            'in_progress' => $this->countByStatusKey('in_progress'),
            'pending' => $base()->pending()->count(),
            'resolved' => $base()->resolved()->count(),
            'closed' => $base()->closed()->count(),
            'urgent' => $this->countUrgent(),
            'overdue' => $base()->overdue()->count(),
            'today' => $base()->whereDate('created_at', today())->count(),
            'week' => $base()->where('created_at', '>=', now()->startOfWeek())->count(),
            'month' => $base()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $statuses = TicketStatus::active()->ordered()->get();

        $statusCounts = Ticket::query()
            ->selectRaw('ticket_statuses.key as status_key, count(*) as total')
            ->join('ticket_statuses', 'tickets.status_id', '=', 'ticket_statuses.id')
            ->groupBy('ticket_statuses.key')
            ->pluck('total', 'status_key')
            ->map(fn ($value) => (int) $value)
            ->toArray();

        $status_data = [];
        $status_colors = [];
        foreach ($statuses as $status) {
            $status_data[$status->name] = $statusCounts[$status->key] ?? 0;
            $status_colors[$status->name] = self::STATUS_COLORS[$status->color] ?? '#64748b';
        }

        $categoryData = $this->groupBy('category');

        return view('dashboard.admin', [
            'stats' => $stats,
            'status_data' => $status_data,
            'status_colors' => $status_colors,
            'category_data' => $categoryData,
            'department_data' => $this->groupBy('department'),
            'priority_data' => $this->groupBy('priority'),
            'time_data' => $this->groupByDay(),
            'workload_data' => $this->workload(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Support dashboard
    |--------------------------------------------------------------------------
    */

    protected function support(): View
    {
        $staff = auth()->user();

        $stats = [
            'my_assigned' => Ticket::assignedTo($staff->id)->unresolved()->count(),
            'my_in_progress' => Ticket::assignedTo($staff->id)
                ->whereHas('status', fn ($q) => $q->where('key', 'in_progress'))
                ->count(),
            'unassigned' => Ticket::unassigned()->unresolved()->count(),
            'open' => Ticket::open()->count(),
            'urgent' => $this->countUrgent(),
            'pending_user' => Ticket::whereHas('status', fn ($q) => $q->where('key', 'pending_user'))->count(),
            'recently_resolved' => Ticket::resolved()
                ->where('resolved_at', '>=', now()->subDays(7))
                ->count(),
            'overdue' => Ticket::overdue()->count(),
        ];

        $with = fn () => ['requester', 'status', 'priority', 'category', 'assignedUser'];

        return view('dashboard.support', [
            'stats' => $stats,
            'unassigned_tickets' => Ticket::unassigned()->unresolved()->with($with())->latest()->limit(5)->get(),
            'my_assigned_tickets' => Ticket::assignedTo($staff->id)->unresolved()->with($with())->latest()->limit(5)->get(),
            'overdue_tickets' => Ticket::overdue()->with($with())->orderBy('due_at')->limit(5)->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Requester dashboard
    |--------------------------------------------------------------------------
    */

    protected function requester(): View
    {
        $user = auth()->user();

        $stats = [
            'open' => $user->tickets()->open()->count(),
            'in_progress' => $user->tickets()
                ->whereHas('status', fn ($q) => $q->where('key', 'in_progress'))
                ->count(),
            'pending' => $user->tickets()->pending()->count(),
            'resolved' => $user->tickets()->resolved()->count(),
            'closed' => $user->tickets()->closed()->count(),
        ];

        return view('dashboard.requester', ['stats' => $stats]);
    }

    /*
    |--------------------------------------------------------------------------
    | Shared aggregates
    |--------------------------------------------------------------------------
    */

    protected function countByStatusKey(string $key): int
    {
        return Ticket::whereHas('status', fn ($q) => $q->where('key', $key))->count();
    }

    protected function countUrgent(): int
    {
        return Ticket::whereHas('priority', fn ($q) => $q->where('key', 'urgent'))
            ->unresolved()
            ->count();
    }

    /**
     * Group ticket counts by a dimension (status/category/department/priority).
     */
    protected function groupBy(string $dimension): array
    {
        $map = [
            'status' => ['ticket_statuses', 'status_id', 'name'],
            'category' => ['categories', 'category_id', 'name'],
            'department' => ['departments', 'department_id', 'name'],
            'priority' => ['priorities', 'priority_id', 'name'],
        ];

        [$table, $column, $label] = $map[$dimension];

        $base = Ticket::query()
            ->join($table, 'tickets.'.$column, '=', $table.'.id');

        if (! auth()->user()->isStaff()) {
            $base->where('tickets.requester_id', auth()->id());
        }

        return $base
            ->selectRaw($table.'.name as label, count(*) as total')
            ->groupBy($table.'.id', $table.'.name')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->toArray();
    }

    /**
     * Ticket volume per day for the last 30 days.
     */
    protected function groupByDay(): array
    {
        $user = auth()->user();
        $start = now()->subDays(29)->startOfDay();

        $query = Ticket::query()
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->where('created_at', '>=', $start)
            ->groupBy('day');

        if (! $user->isStaff()) {
            $query->where('requester_id', $user->id);
        }

        $rows = $query->pluck('total', 'day')->mapWithKeys(fn ($v, $k) => [$k => (int) $v]);

        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $result[$day] = $rows[$day] ?? 0;
        }

        return $result;
    }

    /**
     * Open/unresolved tickets per support staff member, including staff with none.
     */
    protected function workload(): array
    {
        $counts = Ticket::query()
            ->whereHas('status', fn ($q) => $q->where('key', 'in_progress'))
            ->whereNotNull('assigned_to')
            ->whereHas('assignedUser', fn ($q) => $q->whereIn('role', ['support', 'admin'])->where('is_active', true))
            ->join('users', 'tickets.assigned_to', '=', 'users.id')
            ->selectRaw('users.id as user_id, count(*) as total')
            ->groupBy('users.id')
            ->pluck('total', 'user_id');

        $staff = User::query()
            ->whereIn('role', ['support', 'admin'])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $staff
            ->mapWithKeys(fn (User $member) => [$member->full_name => (int) ($counts[$member->id] ?? 0)])
            ->sortDesc()
            ->toArray();
    }
}
