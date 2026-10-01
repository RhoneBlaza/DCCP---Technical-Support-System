@extends('layouts.app')

@section('title', 'Edit user')

@section('content')
    <x-page-header
        title="Edit user"
        description="{{ $user->full_name }} · {{ $user->email }}"
        breadcrumb="Users">
        <x-slot:actions>
            @if ($user->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <x-button.secondary type="submit">
                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                    </x-button.secondary>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-form.errors />

    <div class="max-w-3xl mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 bg-surface rounded-lg border border-line p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-form.label for="first_name" required>First name</x-form.label>
                        <x-form.input name="first_name" required value="{{ old('first_name', $user->first_name) }}" />
                    </div>
                    <div>
                        <x-form.label for="middle_name">Middle name</x-form.label>
                        <x-form.input name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" />
                    </div>
                    <div>
                        <x-form.label for="last_name" required>Last name</x-form.label>
                        <x-form.input name="last_name" required value="{{ old('last_name', $user->last_name) }}" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="email" required>Email</x-form.label>
                        <x-form.input name="email" type="email" required value="{{ old('email', $user->email) }}" />
                    </div>
                    <div>
                        <x-form.label for="employee_id">Employee ID</x-form.label>
                        <x-form.input name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" />
                    </div>
                    <div>
                        <x-form.label for="department_id">Department</x-form.label>
                        <x-form.select name="department_id" :options="$departments->pluck('name', 'id')" :selected="old('department_id', $user->department_id)" placeholder="No department" />
                    </div>
                    <div>
                        <x-form.label for="position">Position</x-form.label>
                        <x-form.input name="position" value="{{ old('position', $user->position) }}" />
                    </div>
                    <div>
                        <x-form.label for="contact_number">Contact number</x-form.label>
                        <x-form.input name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" />
                    </div>
                    <div>
                        <x-form.label for="role" required>Role</x-form.label>
                        <x-form.select name="role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])" :selected="old('role', $user->role->value)" />
                    </div>
                    <div class="sm:col-span-2">
                        <input type="hidden" name="is_active" value="0">
                        <x-form.checkbox name="is_active" label="Active account (can sign in)" :checked="old('is_active', $user->is_active)" />
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button.primary type="submit">Save changes</x-button.primary>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-ink-subtle hover:underline">Cancel</a>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <x-card title="Account">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Role</dt>
                        <dd class="text-ink">{{ $user->role->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Account status</dt>
                        <dd class="text-ink">{{ $user->account_status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-faint">Last login</dt>
                        <dd class="text-ink">{{ $user->last_login_at?->format('M j, Y H:i') ?? 'Never' }}</dd>
                    </div>
                </dl>
            </x-card>

            @if ($user->id !== auth()->id())
                <x-card title="Password" description="Reset this user's password. The new temporary password is shown only once.">
                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                        @csrf
                        @method('PATCH')
                        <x-button.secondary type="submit" class="w-full justify-center">Reset password</x-button.secondary>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
@endsection