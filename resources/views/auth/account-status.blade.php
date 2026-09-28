@extends('layouts.guest')

@section('title', 'Account status')

@section('content')
    @php
        $pending = $user->isPendingApproval();
        $rejected = $user->isRejected();
        $suspended = $user->isSuspended();
        $resubmitRequested = $latestRequest?->status === 'resubmit_requested';
    @endphp

    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3
            {{ $suspended ? 'bg-red-50 text-red-600' : ($rejected || $resubmitRequested ? 'bg-orange-50 text-orange-600' : 'bg-navy-50 text-navy-700') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h1 class="text-lg font-bold text-slate-800">Your account is not yet active</h1>
    </div>

    <div class="space-y-4 text-sm text-slate-600">
        @if ($suspended)
            <p>Your account has been <strong>suspended</strong>.</p>
            <p>Please contact the IT Helpdesk at
                <a href="mailto:{{ settings('support_email', '') }}" class="font-medium text-navy-700 hover:underline">{{ settings('support_email', 'the support desk') }}</a>
                if you believe this is a mistake.</p>
        @elseif ($rejected)
            <p>Your registration was <strong>not approved</strong> by the administrator.</p>
            @if ($latestRequest?->decision_note)
                <div class="rounded-lg bg-orange-50 border border-orange-200 p-4 text-orange-800">
                    {{ $latestRequest->decision_note }}
                </div>
            @endif
            <div class="mt-4">
                <a href="{{ route('register') }}" class="block w-full text-center px-4 py-2.5 rounded-lg bg-navy-700 text-white text-sm font-semibold hover:bg-navy-800 transition-colors">Submit a new registration</a>
            </div>
        @elseif ($resubmitRequested)
            <p>The administrator requested <strong>additional information</strong> to complete the verification of your ID.</p>
            <div class="mt-4">
                <a href="{{ route('register') }}" class="block w-full text-center px-4 py-2.5 rounded-lg bg-navy-700 text-white text-sm font-semibold hover:bg-navy-800 transition-colors">Resubmit your ID</a>
            </div>
        @else
            <p>Your registration is <strong>awaiting administrator approval</strong>.</p>
            <p>Once an administrator verifies your ID document, you will be able to sign in. You will be notified here once your account is approved.</p>
            <p class="text-xs text-slate-400">If it has been more than a few working days, contact
                <a href="mailto:{{ settings('support_email', '') }}" class="font-medium text-navy-700 hover:underline">{{ settings('support_email', 'the IT Helpdesk') }}</a>.</p>
        @endif
    </div>

    <p class="text-center text-sm text-slate-500 mt-6">
        Returning to the
        <a href="{{ route('login') }}" class="font-medium text-navy-700 hover:underline">sign-in page</a>
    </p>
@endsection