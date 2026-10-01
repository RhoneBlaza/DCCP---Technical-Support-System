@extends('layouts.app')

@section('title', 'Change password')

@section('content')
    <div class="max-w-xl">
        <x-page-header>
            <x-slot:title>Change password</x-slot:title>
            <x-slot:description>Your password must be updated before you can continue.</x-slot:description>
        </x-page-header>

        <div class="bg-surface rounded-lg border border-line p-5 sm:p-6 mt-6">
            <x-form.errors />

            <form method="POST" action="{{ route('profile.change-password.store') }}" class="space-y-4">
                @csrf

                <x-form.label for="current_password" required>Current password</x-form.label>
                <x-form.input name="current_password" type="password" autocomplete="current-password" required autofocus />

                <x-form.label for="password" required>New password</x-form.label>
                <x-form.input name="password" type="password" autocomplete="new-password" required />
                <p class="text-xs text-ink-subtle mt-1">At least 10 characters, with upper- and lower-case letters and a number.</p>

                <x-form.label for="password_confirmation" required>Confirm new password</x-form.label>
                <x-form.input name="password_confirmation" type="password" autocomplete="new-password" required />

                <div class="flex items-center gap-3 pt-2">
                    <x-button.primary type="submit">Update password</x-button.primary>
                </div>
            </form>
        </div>
    </div>
@endsection