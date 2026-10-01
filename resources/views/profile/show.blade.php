@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="max-w-3xl">
        <x-page-header title="Profile">
            <x-slot:actions>
                <a href="#profile-form" class="inline-flex items-center px-4 py-2 rounded-lg bg-navy-700 text-white text-sm font-medium hover:bg-navy-800">
                    Edit profile
                </a>
            </x-slot:actions>
        </x-page-header>

        <div class="bg-surface rounded-lg border border-line mt-6 overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-navy-700 to-navy-900"></div>
            <div class="px-6 pb-6">
                <div class="flex flex-wrap items-end gap-4 -mt-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-xl bg-surface ring-4 ring-line shadow text-2xl font-bold text-navy-800 dark:text-navy-100">
                        {{ $user->initials }}
                    </div>
                    <div class="pb-1 min-w-0">
                        <h2 class="text-lg font-bold text-ink">{{ $user->full_name }}</h2>
                        <p class="text-sm text-ink-subtle">{{ $user->role->label() }}{{ $user->department ? ' · '.$user->department->name : '' }}</p>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 mt-6 text-sm">
                    @if ($user->employee_id)
                        <div>
                            <dt class="text-xs font-medium text-ink-subtle">Employee ID</dt>
                            <dd class="text-ink font-medium">{{ $user->employee_id }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-ink-subtle">Email</dt>
                        <dd class="text-ink font-medium">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-subtle">Contact number</dt>
                        <dd class="text-ink font-medium">{{ $user->contact_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-subtle">Position / Designation</dt>
                        <dd class="text-ink font-medium">{{ $user->position ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-subtle">Department</dt>
                        <dd class="text-ink font-medium">{{ $user->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-subtle">Last login</dt>
                        <dd class="text-ink font-medium">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') : 'Never' }}</dd>
                    </div>
                </dl>

                <a href="{{ route('profile.change-password') }}" class="mt-6 inline-flex items-center gap-1 text-sm text-navy-700 dark:text-navy-200 hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4v-4l8-8a6 6 0 0111 0z"/></svg>
                    Change password
                </a>
            </div>
        </div>

        <div id="profile-form" class="bg-surface rounded-lg border border-line mt-6 p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-ink mb-4">Edit details</h3>

            @if (session('status'))
                <x-alert type="success" dismissible>{{ session('status') }}</x-alert>
            @endif

            <x-form.errors />

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-form.label for="first_name" required>First name</x-form.label>
                        <x-form.input name="first_name" required :value="$user->first_name" />
                    </div>
                    <div>
                        <x-form.label for="middle_name">Middle name</x-form.label>
                        <x-form.input name="middle_name" :value="$user->middle_name" />
                    </div>
                    <div>
                        <x-form.label for="last_name" required>Last name</x-form.label>
                        <x-form.input name="last_name" required :value="$user->last_name" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="employee_id">Employee ID</x-form.label>
                        <x-form.input name="employee_id" :value="$user->employee_id" />
                    </div>
                    <div>
                        <x-form.label for="email" required>Email</x-form.label>
                        <x-form.input name="email" type="email" required :value="$user->email" />
                    </div>
                    <div>
                        <x-form.label for="contact_number">Contact number</x-form.label>
                        <x-form.input name="contact_number" :value="$user->contact_number" />
                    </div>
                    <div>
                        <x-form.label for="position">Position / Designation</x-form.label>
                        <x-form.input name="position" :value="$user->position" />
                    </div>
                </div>

                <div>
                    @php
                        $themeOptions = collect(\App\Enums\ThemePreference::cases())
                            ->mapWithKeys(fn ($option) => [
                                $option->value => $option === \App\Enums\ThemePreference::System
                                    ? $option->label().' (follow this device)'
                                    : $option->label(),
                            ])
                            ->all();
                    @endphp
                    <x-form.label for="theme">Colour theme</x-form.label>
                    <x-form.select
                        name="theme"
                        :options="$themeOptions"
                        :selected="$user->theme?->value ?? \App\Enums\ThemePreference::System->value"
                        x-on:change="$store.theme.set($event.target.value)" />
                    <p class="mt-1 text-xs text-ink-faint">
                        Applies straight away in this browser and is saved to your account.
                    </p>
                </div>

                <div>
                    <x-form.label for="photo">Profile photo <span class="text-xs font-normal text-ink-faint">(JPG/PNG, up to 2&nbsp;MB)</span></x-form.label>
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png"
                        class="block w-full text-sm text-ink-muted file:mr-3 file:rounded-lg file:border-0 file:bg-navy-50 dark:file:bg-navy-500/20 file:px-3 file:py-2 file:text-sm file:font-medium file:text-navy-700 dark:file:text-navy-200 hover:file:bg-navy-100 dark:hover:file:bg-navy-500/20">
                    <x-form.error name="photo" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-navy-700 text-white text-sm font-medium hover:bg-navy-800">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection