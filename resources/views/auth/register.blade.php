@extends('layouts.guest')

@section('title', 'Create your account')

@section('content')
    <x-form.errors />

    <form method="POST" action="{{ route('register.attempt') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-form.label for="id_type" required>ID type</x-form.label>
                <x-form.select name="id_type" :options="[
                    '' => 'Select your ID type',
                    'school_id' => 'School ID',
                    'employee_id' => 'Employee ID',
                    'government_id' => 'Government ID',
                ]" :selected="old('id_type')" />
            </div>

            <div>
                <x-form.label for="id_number" required>ID number</x-form.label>
                <x-form.input name="id_number" required autofocus placeholder="Number shown on your ID" />
            </div>
        </div>

        <div>
            <x-form.label for="id_image" required>ID photo / scan</x-form.label>
            <x-form.input name="id_image" type="file" accept=".jpg,.jpeg,.png,.pdf" required />
            <p class="text-xs text-slate-400 mt-1">JPG, PNG or PDF up to 5 MB. Only administrators can view this document.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-form.label for="first_name" required>First name</x-form.label>
                <x-form.input name="first_name" required />
            </div>

            <div>
                <x-form.label for="last_name" required>Last name</x-form.label>
                <x-form.input name="last_name" required />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-form.label for="email" required>Email address</x-form.label>
                <x-form.input name="email" type="email" autocomplete="email" required placeholder="you@dccp-bangued.edu.ph" />
            </div>

            <div>
                <x-form.label for="contact_number" required>Contact number</x-form.label>
                <x-form.input name="contact_number" required placeholder="e.g. 0917 123 4567" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-form.label for="department_id" required>Department</x-form.label>
                <x-form.select name="department_id" :options="$departments->pluck('name', 'id')" placeholder="Select your department" />
            </div>

            <div>
                <x-form.label for="position" required>Position</x-form.label>
                <x-form.input name="position" required placeholder="e.g. Faculty, Registrar staff" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-form.label for="password" required>Password</x-form.label>
                <x-form.input
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    placeholder="At least 10 characters, mixed case and numbers" />
            </div>

            <div>
                <x-form.label for="password_confirmation" required>Confirm password</x-form.label>
                <x-form.input
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    placeholder="Repeat your password" />
            </div>
        </div>

        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
            @php
                $organization = settings('organization_name', 'the institution');
                $privacyLabel = "I consent to the collection, processing and retention of my personal data and ID document for the purpose of verifying my account under {$organization}'s data privacy policy. I understand only authorized administrators may view it.";
            @endphp
            <x-form.checkbox name="privacy_consent" :label="$privacyLabel" />
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-navy-700 text-white text-sm font-semibold hover:bg-navy-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500 transition-colors">
            Request account
        </button>
    </form>

    <p class="text-center text-sm text-slate-500 mt-4">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-navy-700 hover:underline">Sign in</a>
    </p>
@endsection