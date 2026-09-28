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

        <div class="bg-white rounded-lg border border-slate-200 mt-6 overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-navy-700 to-navy-900"></div>
            <div class="px-6 pb-6">
                <div class="flex flex-wrap items-end gap-4 -mt-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-xl bg-white ring-4 ring-white shadow text-2xl font-bold text-navy-800">
                        {{ $user->initials }}
                    </div>
                    <div class="pb-1 min-w-0">
                        <h2 class="text-lg font-bold text-slate-800">{{ $user->full_name }}</h2>
                        <p class="text-sm text-slate-500">{{ $user->role->label() }}{{ $user->department ? ' · '.$user->department->name : '' }}</p>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 mt-6 text-sm">
                    @if ($user->employee_id)
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Employee ID</dt>
                            <dd class="text-slate-800 font-medium">{{ $user->employee_id }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Email</dt>
                        <dd class="text-slate-800 font-medium">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Contact number</dt>
                        <dd class="text-slate-800 font-medium">{{ $user->contact_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Position / Designation</dt>
                        <dd class="text-slate-800 font-medium">{{ $user->position ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Department</dt>
                        <dd class="text-slate-800 font-medium">{{ $user->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Last login</dt>
                        <dd class="text-slate-800 font-medium">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') : 'Never' }}</dd>
                    </div>
                </dl>

                <a href="{{ route('profile.change-password') }}" class="mt-6 inline-flex items-center gap-1 text-sm text-navy-700 hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4v-4l8-8a6 6 0 0111 0z"/></svg>
                    Change password
                </a>
            </div>
        </div>

        <div id="profile-form" class="bg-white rounded-lg border border-slate-200 mt-6 p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Edit details</h3>

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
                    <x-form.label for="photo">Profile photo <span class="text-xs font-normal text-slate-400">(JPG/PNG, up to 2&nbsp;MB)</span></x-form.label>
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png"
                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-navy-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-navy-700 hover:file:bg-navy-100">
                    <x-form.error name="photo" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-navy-700 text-white text-sm font-medium hover:bg-navy-800">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection