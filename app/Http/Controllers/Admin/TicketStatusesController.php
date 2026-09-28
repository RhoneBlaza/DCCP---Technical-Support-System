<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatusType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTicketStatusRequest;
use App\Models\TicketStatus;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketStatusesController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TicketStatus::class);

        $statuses = TicketStatus::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim($request->string('search'));

                $query->where(fn ($q) => $q->whereLike('name', $term)->orWhereLike('key', $term));
            })
            ->ordered()
            ->withCount('tickets')
            ->paginate(25)
            ->withQueryString();

        return view('admin.ticket-statuses.index', [
            'statuses' => $statuses,
            'types' => TicketStatusType::cases(),
            'colors' => config('tsts.status_colors'),
        ]);
    }

    public function store(StoreTicketStatusRequest $request): RedirectResponse
    {
        $this->authorize('create', TicketStatus::class);

        $status = TicketStatus::create($request->validated() + [
            'key' => $request->filled('key') ? $request->input('key') : (string) str($request->input('name'))->slug('_'),
            'color' => $request->input('color', 'gray'),
            'pauses_sla' => $request->input('type') === TicketStatusType::Pending->value,
            'is_system' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->audit->log('status_created', $status, 'Ticket status created: '.$status->name);

        return redirect()->route('admin.statuses.index')->with('status', 'Ticket status created.');
    }

    public function update(StoreTicketStatusRequest $request, TicketStatus $status): RedirectResponse
    {
        $this->authorize('update', $status);

        if ($status->is_system && $request->filled('key') && $request->input('key') !== $status->key) {
            throw ValidationException::withMessages(['key' => 'The key of a system status cannot be changed.']);
        }

        if ($status->is_essential && ! $request->boolean('is_active', true)) {
            throw ValidationException::withMessages(['is_active' => 'This essential system status cannot be deactivated.']);
        }

        $old = $status->only(['name', 'color', 'sort_order', 'type', 'pauses_sla', 'is_active']);

        $status->update($request->validated() + [
            'pauses_sla' => $request->input('type') === TicketStatusType::Pending->value,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->audit->log('status_updated', $status, 'Ticket status updated: '.$status->name, $old, $status->only(['name', 'color', 'sort_order', 'type', 'pauses_sla', 'is_active']));

        return redirect()->route('admin.statuses.index')->with('status', 'Ticket status updated.');
    }

    public function toggleActive(TicketStatus $status): RedirectResponse
    {
        $this->authorize('update', $status);

        if ($blocker = $status->deactivationBlocker()) {
            return back()->withErrors(['error' => $blocker]);
        }

        $status->update(['is_active' => ! $status->is_active]);

        $this->audit->log('status_activation_changed', $status, 'Ticket status activation state changed: '.$status->name);

        return back()->with('status', $status->is_active ? 'Ticket status activated.' : 'Ticket status deactivated.');
    }
}
