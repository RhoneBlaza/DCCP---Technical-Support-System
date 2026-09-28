@extends('layouts.app')

@section('title', 'Submit a ticket')

@php
    $duplicateWarning = $errors->get('duplicate_ack')[0] ?? null;
@endphp

@section('content')
    <div class="max-w-3xl">
        <x-page-header title="Submit a ticket" description="Describe the issue you are experiencing and our IT team will pick it up." />

        <div class="bg-white rounded-lg border border-slate-200 p-5 sm:p-6 mt-6">
            <x-form.errors :except="['duplicate_ack']" />

            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                @if (auth()->user()->isStaff() && $requesters->isNotEmpty())
                    <div>
                        <x-form.label for="requester_id" required>Requested by</x-form.label>
                        <x-form.select name="requester_id" :options="$requesters->pluck('full_name', 'id')" :selected="old('requester_id', auth()->id())" placeholder="Select the requester" />
                    </div>
                @endif

                <div>
                    <x-form.label for="subject" required>Subject</x-form.label>
                    <x-form.input name="subject" required autofocus placeholder="Brief summary of the problem" value="{{ old('subject') }}" />
                </div>

                <div>
                    <x-form.label for="description" required>Description</x-form.label>
                    <x-form.textarea name="description" rows="6" required placeholder="What happened, when, and what you expected to happen. Include error messages if any." />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="category_id" required>Category</x-form.label>
                        <select name="category_id" id="category_id" required class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-navy-200 focus:border-navy-500">
                            <option value="">{{ 'Select a category' }}</option>
                            @foreach ($categories as $category)
                                <optgroup label="{{ $category->name }}">
                                    @foreach ($category->children as $child)
                                        <option value="{{ $child->id }}" @selected(old('category_id') == $child->id)>{{ $child->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-form.error name="category_id" />
                    </div>

                    <div>
                        <x-form.label for="department_id" required>Department</x-form.label>
                        <x-form.select name="department_id" :options="$departments->pluck('name', 'id')" :selected="old('department_id', auth()->user()->department_id)" placeholder="Select department" />
                    </div>

                    <div>
                        <x-form.label for="priority_id">Priority</x-form.label>
                        <x-form.select name="priority_id" :options="$priorities->pluck('name', 'id')" :selected="old('priority_id')" placeholder="Select priority" />
                        <p class="text-xs text-slate-400 mt-1">Need urgent help? Please call the IT Helpdesk directly.</p>
                    </div>

                    <div>
                        <x-form.label for="location">Location / Room</x-form.label>
                        <x-form.input name="location" placeholder="e.g. Registrar Office, Room 204" value="{{ old('location') }}" />
                    </div>

                    <div>
                        <x-form.label for="device_type">Device type</x-form.label>
                        <x-form.input name="device_type" placeholder="e.g. Desktop, Laptop, Printer" value="{{ old('device_type') }}" />
                    </div>

                    <div>
                        <x-form.label for="asset_number">Asset / Serial number</x-form.label>
                        <x-form.input name="asset_number" placeholder="e.g. DCCP-2024-00123" value="{{ old('asset_number') }}" />
                    </div>

                    <div>
                        <x-form.label for="contact_number">Best contact number</x-form.label>
                        <x-form.input name="contact_number" value="{{ old('contact_number', auth()->user()->contact_number) }}" />
                    </div>
                </div>

                <div>
                    <x-form.label for="attachments">Attachments <span class="text-xs font-normal text-slate-400">({{ settings('max_attachments_per_message', 5) }} max · {{ settings('max_attachment_kb', 5120) }} KB each · {{ collect(settings('allowed_attachment_extensions', []))->implode(', ') }})</span></x-form.label>
                    <input type="file" name="attachments[]" id="attachments" multiple
                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-navy-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-navy-700 hover:file:bg-navy-100">
                    <x-form.error name="attachments" />
                    <x-form.error name="attachments.*" />
                </div>

                @if ($duplicateWarning)
                    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4" role="alert">
                        <p class="text-sm text-amber-900">{{ $duplicateWarning }}</p>
                        <div class="mt-3">
                            <input type="hidden" name="duplicate_ack" value="0">
                            <x-form.checkbox
                                name="duplicate_ack"
                                label="This is a different problem — submit a new ticket anyway"
                                :checked="old('duplicate_ack')"
                                :show-error="false" />
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg bg-navy-700 text-white text-sm font-semibold hover:bg-navy-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Submit ticket
                    </button>
                    <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection