<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePriorityRequest;
use App\Models\Priority;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrioritiesController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Priority::class);

        $priorities = Priority::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim($request->string('search'));

                $query->where(fn ($q) => $q->whereLike('name', $term)->orWhereLike('key', $term));
            })
            ->orderBy('level', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.priorities.index', ['priorities' => $priorities]);
    }

    public function store(StorePriorityRequest $request): RedirectResponse
    {
        $this->authorize('create', Priority::class);

        $priority = Priority::create($request->validated() + ['is_requester_selectable' => $request->boolean('is_requester_selectable'), 'is_system' => false]);
        $priority->forceFill(['is_active' => $request->boolean('is_active', true)])->save();

        $this->audit->log('priority_created', $priority, 'Priority created: '.$priority->name);

        return redirect()->route('admin.priorities.index')->with('status', 'Priority created.');
    }

    public function update(StorePriorityRequest $request, Priority $priority): RedirectResponse
    {
        $this->authorize('update', $priority);

        if ($priority->is_system && $request->input('key') !== $priority->key) {
            throw ValidationException::withMessages(['key' => 'The key of a system priority cannot be changed.']);
        }

        $old = $priority->only(['name', 'description', 'sla_hours', 'level', 'is_requester_selectable', 'is_active']);

        $priority->update($request->validated() + ['is_requester_selectable' => $request->boolean('is_requester_selectable')]);
        $priority->forceFill(['is_active' => $request->boolean('is_active', true)])->save();

        $this->audit->log('priority_updated', $priority, 'Priority updated: '.$priority->name, $old, $priority->only(['name', 'description', 'sla_hours', 'level', 'is_requester_selectable', 'is_active']));

        return redirect()->route('admin.priorities.index')->with('status', 'Priority updated.');
    }

    public function toggleActive(Priority $priority): RedirectResponse
    {
        $this->authorize('update', $priority);

        if ($priority->is_active && $priority->tickets()->exists()) {
            return back()->withErrors(['error' => 'Priorities already referenced by tickets cannot be deactivated.']);
        }

        $priority->update(['is_active' => ! $priority->is_active]);

        $this->audit->log('priority_status_changed', $priority, 'Priority activation state changed: '.$priority->name);

        return back()->with('status', 'Priority updated.');
    }
}
