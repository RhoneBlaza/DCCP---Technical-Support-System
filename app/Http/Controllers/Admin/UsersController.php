<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('department')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim($request->string('search'));

                $query->where(function ($query) use ($term) {
                    $query
                        ->whereLike('first_name', $term)
                        ->orWhereLike('last_name', $term)
                        ->orWhereLike('email', $term)
                        ->orWhereLike('employee_id', $term);
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
            ->when($request->query('status') === 'pending', function ($query) {
                $query
                    ->where('role', UserRole::Requester->value)
                    ->where('account_status', 'pending');
            })
            ->when($request->filled('status') && $request->query('status') !== 'pending', function ($query) use ($request) {
                $query->where('is_active', $request->query('status') === 'inactive' ? false : true);
            })
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => UserRole::cases()]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => UserRole::cases(),
            'departments' => Department::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $temporaryPassword = Str::password(14, symbols: false);

        $user = User::create($request->safe()->all()
            + [
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'is_active' => true,
                'account_status' => AccountStatus::Approved,
            ]);

        $this->audit->log('user_created', $user, 'User account created for '.$user->full_name, null, $request->safe()->toArray());

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created. Their temporary password was set only once.')
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_email', $user->email);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load('department'),
            'roles' => UserRole::cases(),
            'departments' => Department::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $newRole = UserRole::tryFrom($request->input('role'));
        $newActive = $request->boolean('is_active');

        $this->guardAgainstInvalidAdminChanges($user, $newRole, $newActive);

        $old = $user->only(['role', 'is_active', 'department_id', 'position']);

        $user->fill($request->safe()->except(['role', 'is_active']));
        $user->forceFill(['role' => $newRole, 'is_active' => $newActive])->save();

        $this->audit->log('user_updated', $user, 'User account updated for '.$user->full_name, $old, $request->safe()->toArray());

        return redirect()->route('admin.users.index')->with('status', 'User updated successfully.');
    }

    public function toggleSuspend(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        // Prevent suspending/reactivating oneself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot suspend or reactivate your own account.']);
        }

        // Prevent suspending/reactivating the last active administrator if it would leave none
        if ($user->isAdmin() && $user->is_active && ! $this->hasAnotherActiveAdmin($user)) {
            return back()->withErrors(['error' => 'The last active administrator cannot be suspended or reactivated.']);
        }

        if ($user->account_status === AccountStatus::Suspended) {
            // Reactivate: set account_status to approved
            $user->update(['account_status' => AccountStatus::Approved]);

            $this->audit->log('user_reactivated', $user, 'User account reactivated for '.$user->full_name);

            return back()->with('status', 'User reactivated successfully.');
        } else {
            // Suspend: set account_status to suspended
            $user->update(['account_status' => AccountStatus::Suspended]);

            $this->audit->log('user_suspended', $user, 'User account suspended for '.$user->full_name);

            return back()->with('status', 'User suspended successfully.');
        }
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        // Load the user with department and assigned tickets
        $user->load(['department', 'assignedTickets']);

        return view('admin.users.show', ['user' => $user]);
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        // Prevent deleting oneself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Prevent deleting the last administrator
        if ($user->isAdmin() && ! $this->hasAnotherAdmin($user)) {
            return back()->withErrors(['error' => 'The last administrator cannot be deleted.']);
        }

        // Check for open assigned tickets
        $unresolvedAssignedTickets = $user->assignedTickets()->unresolved()->count();

        if ($unresolvedAssignedTickets > 0) {
            return back()->withErrors([
                'error' => "This user has {$unresolvedAssignedTickets} unresolved ticket(s) assigned. Please resolve or reassign them before deleting.",
            ]);
        }

        // Soft delete the user
        $user->delete();

        $this->audit->log('user_deleted', $user, 'User account deleted for '.$user->full_name);

        return redirect()->route('admin.users.index')->with('status', 'User deleted successfully.');
    }

    protected function hasAnotherAdmin(User $user): bool
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->whereKeyNot($user->id)
            ->exists();
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $temporaryPassword = Str::password(14, symbols: false);

        $user->update([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ]);

        // Invalidate any "remember me" cookie the account holder (or an
        // attacker) may already possess.
        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->audit->log('password_reset_by_admin', $user, 'Password reset by administrator for '.$user->full_name);

        return back()
            ->with('status', 'Password reset. The temporary password is shown only once.')
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_email', $user->email);
    }

    public function approve(User $user): RedirectResponse
    {
        $this->authorize('approve', $user);

        $user->update([
            'is_active' => true,
            'account_status' => AccountStatus::Approved,
        ]);

        $this->audit->log('registration_approved', $user, 'Registration approved for '.$user->full_name.'. '.$user->full_name.' can now sign in.');

        return back()->with('status', 'Registration approved. '.$user->full_name.' can now sign in.');
    }

    public function reject(User $user): RedirectResponse
    {
        $this->authorize('reject', $user);

        $user->update([
            'is_active' => false,
            'account_status' => AccountStatus::Rejected,
        ]);

        $this->audit->log('registration_rejected', $user, 'Registration rejected for '.$user->full_name.'. They cannot sign in.');

        return back()->with('status', 'Registration rejected. '.$user->full_name.' cannot sign in.');
    }

    protected function guardAgainstInvalidAdminChanges(User $user, ?UserRole $newRole, bool $newActive): void
    {
        $isAdmin = $user->isAdmin();
        $demoting = $isAdmin && $newRole !== UserRole::Admin;
        $deactivating = $isAdmin && ! $newActive;

        if ($user->id === auth()->id() && $deactivating) {
            abort(422, 'You cannot deactivate your own account.');
        }

        if ($isAdmin && ($demoting || $deactivating) && ! $this->hasAnotherActiveAdmin($user)) {
            abort(422, 'The last active administrator cannot be demoted or deactivated.');
        }
    }

    protected function hasAnotherActiveAdmin(User $user): bool
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->exists();
    }
}
