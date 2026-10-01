<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Models\Department;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentsController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->withCount('users')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim($request->string('search'));

                $query->where(fn ($q) => $q->whereLike('name', $term)->orWhereLike('code', $term));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.departments.index', ['departments' => $departments]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $department = Department::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        $this->audit->log('department_created', $department, 'Department created: '.$department->name);

        return redirect()->route('admin.departments.index')->with('status', 'Department created.');
    }

    public function update(StoreDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $old = $department->only(['name', 'code', 'description', 'is_active']);

        $department->update($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        $this->audit->log('department_updated', $department, 'Department updated: '.$department->name, $old, $department->only(['name', 'code', 'description', 'is_active']));

        return redirect()->route('admin.departments.index')->with('status', 'Department updated.');
    }

    public function toggleActive(Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($department->users()->where('is_active', true)->exists() && $department->is_active) {
            return back()->withErrors(['error' => 'Departments with active users cannot be deactivated.']);
        }

        $department->update(['is_active' => ! $department->is_active]);

        $this->audit->log('department_status_changed', $department, 'Department activation state changed: '.$department->name);

        return back()->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        if ($department->users()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete department that has users assigned.']);
        }

        if ($department->tickets()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete department that has tickets associated.']);
        }

        $this->audit->log('department_deleted', $department, 'Department deleted: '.$department->name);

        $department->delete();

        return redirect()->route('admin.departments.index')->with('status', 'Department deleted.');
    }
}
