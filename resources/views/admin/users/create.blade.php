@extends('layouts.app')

@section('title', 'Create user')

@section('content')
    <x-page-header
        title="Create user"
        description="Create a staff account. A temporary password is generated and shown only once."
        breadcrumb="Users" />

    <x-form.errors />

    <div class="max-w-3xl mt-6">
        <div class="bg-surface rounded-lg border border-line p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-form.label for="first_name" required>First name</x-form.label>
                        <x-form.input name="first_name" required autofocus value="{{ old('first_name') }}" />
                    </div>
                    <div>
                        <x-form.label for="middle_name">Middle name</x-form.label>
                        <x-form.input name="middle_name" value="{{ old('middle_name') }}" />
                    </div>
                    <div>
                        <x-form.label for="last_name" required>Last name</x-form.label>
                        <x-form.input name="last_name" required value="{{ old('last_name') }}" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="email" required>Email</x-form.label>
                        <x-form.input name="email" type="email" required value="{{ old('email') }}" />
                    </div>
                    <div>
                        <x-form.label for="employee_id">Employee ID</x-form.label>
                        <x-form.input name="employee_id" value="{{ old('employee_id') }}" />
                    </div>
                    <div>
                        <x-form.label for="department_id">Department</x-form.label>
                        <x-form.select name="department_id" :options="$departments->pluck('name', 'id')" :selected="old('department_id')" placeholder="No department" />
                    </div>
                    <div>
                        <x-form.label for="position">Position</x-form.label>
                        <x-form.input name="position" value="{{ old('position') }}" />
                    </div>
                    <div>
                        <x-form.label for="contact_number">Contact number</x-form.label>
                        <x-form.input name="contact_number" value="{{ old('contact_number') }}" />
                    </div>
                    <div>
                        <x-form.label for="role" required>Role</x-form.label>
                        <x-form.select name="role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])" :selected="old('role')" placeholder="Select role" />
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button.primary type="submit">Create user</x-button.primary>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-ink-subtle hover:underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection