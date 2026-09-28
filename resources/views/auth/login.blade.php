@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <x-form.errors />

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf

        <x-form.label for="email" required>Email address</x-form.label>
        <x-form.input name="email" type="email" autocomplete="email" required autofocus placeholder="you@dccp-bangued.edu.ph" />

        <div>
            <div class="flex items-center justify-between mb-1">
                <x-form.label for="password" required>Password</x-form.label>
                <a href="{{ route('password.request') }}" class="text-xs font-medium text-navy-700 hover:underline">Forgot password?</a>
            </div>
            <x-form.input name="password" type="password" autocomplete="current-password" required placeholder="••••••••" />
        </div>

        <div class="flex items-center justify-between pt-1">
            <x-form.checkbox name="remember" label="Remember me" />
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-navy-700 text-white text-sm font-semibold hover:bg-navy-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500 transition-colors">
            Sign in
        </button>

        <p class="text-center text-sm text-slate-500">
            Don't have an account yet?
            <a href="{{ route('register') }}" class="font-medium text-navy-700 hover:underline">Request one</a>
        </p>
    </form>
@endsection