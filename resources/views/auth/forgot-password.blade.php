@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <p class="text-sm text-slate-600 mb-5">Enter your email address and we'll email you a link to reset your password.</p>

    @if (session('status'))
        <x-alert type="success">{{ session('status') }}</x-alert>
    @endif

    <x-form.errors />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-form.label for="email" required>Email address</x-form.label>
        <x-form.input name="email" type="email" autocomplete="email" required autofocus placeholder="you@dccp-bangued.edu.ph" />

        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-navy-700 text-white text-sm font-semibold hover:bg-navy-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500 transition-colors">
            Send reset link
        </button>
    </form>

    <p class="text-center text-xs text-slate-500 mt-4">
        <a href="{{ route('login') }}" class="font-medium text-navy-700 hover:underline">&larr; Back to sign in</a>
    </p>
@endsection