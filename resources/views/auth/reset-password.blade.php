@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <p class="text-sm text-slate-600 mb-5">Choose a new password for your account.</p>

    <x-form.errors />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.label for="email" required>Email address</x-form.label>
        <x-form.input name="email" type="email" autocomplete="email" required autofocus placeholder="you@dccp-bangued.edu.ph" value="{{ $email }}" />

        <x-form.label for="password" required>New password</x-form.label>
        <x-form.input name="password" type="password" autocomplete="new-password" required placeholder="At least 10 characters" />
        <p class="text-xs text-slate-500 mt-1">Use at least 10 characters, with upper- and lower-case letters and a number.</p>

        <x-form.label for="password_confirmation" required>Confirm new password</x-form.label>
        <x-form.input name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Repeat the new password" />

        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-navy-700 text-white text-sm font-semibold hover:bg-navy-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500 transition-colors">
            Reset password
        </button>
    </form>
@endsection