@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <x-page-header title="Settings" description="System-wide configuration. Changes are logged in the Activity Monitor." />

    <x-form.errors />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        @csrf
        @method('PUT')

        <div class="lg:col-span-2 space-y-6">
            <x-card title="Identity" flush>
                <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-line-soft">
                    <div>
                        <x-form.label for="organization_name">Organization name</x-form.label>
                        <x-form.input name="organization_name" :value="settings('organization_name')" />
                    </div>
                    <div>
                        <x-form.label for="system_name">System name</x-form.label>
                        <x-form.input name="system_name" :value="settings('system_name')" />
                    </div>
                    <div>
                        <x-form.label for="system_short_name">Short name</x-form.label>
                        <x-form.input name="system_short_name" :value="settings('system_short_name')" />
                    </div>
                    <div>
                        <x-form.label for="ticket_prefix">Ticket number prefix</x-form.label>
                        <x-form.input name="ticket_prefix" :value="settings('ticket_prefix')" />
                    </div>
                    <div>
                        <x-form.label for="support_email">Support email</x-form.label>
                        <x-form.input name="support_email" type="email" :value="settings('support_email')" />
                    </div>
                    <div>
                        <x-form.label for="support_phone">Support phone</x-form.label>
                        <x-form.input name="support_phone" :value="settings('support_phone')" />
                    </div>
                </div>
                <div class="p-4 sm:p-5">
                    <x-form.label for="default_priority_id">Default priority for new tickets</x-form.label>
                    <x-form.select name="default_priority_id" :options="collect($priorities)->mapWithKeys(fn ($p) => [$p->id => $p->name])->prepend('No default', '')" :selected="settings('default_priority_id')" />
                </div>
            </x-card>

            <x-card title="Attachments" flush>
                <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="max_attachment_kb">Maximum file size (KB)</x-form.label>
                        <x-form.input name="max_attachment_kb" type="number" :value="settings('max_attachment_kb')" />
                    </div>
                    <div>
                        <x-form.label for="max_attachments_per_message">Maximum files per message</x-form.label>
                        <x-form.input name="max_attachments_per_message" type="number" :value="settings('max_attachments_per_message')" />
                    </div>
                </div>
                <div class="px-4 sm:px-5 pb-5">
                    <p class="text-sm font-medium text-ink mb-2">Allowed attachment extensions</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @php
                            $allowed = settings('allowed_attachment_extensions', []);
                        @endphp
                        @foreach ($allowlist as $extension)
                            <label class="inline-flex items-center gap-2 text-sm text-ink cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="allowed_attachment_extensions[]"
                                    value="{{ $extension }}"
                                    @checked(in_array($extension, $allowed, true))
                                    class="h-4 w-4 rounded border-line-strong text-navy-600 dark:text-navy-300 focus:ring-2 focus:ring-navy-500 dark:focus:ring-navy-400">
                                .{{ $extension }}
                            </label>
                        @endforeach
                        @if ($errors->has('allowed_attachment_extensions'))
                            <x-form.error name="allowed_attachment_extensions" />
                        @endif
                    </div>
                </div>
            </x-card>

            <x-card title="Workflow & retention" flush>
                <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-form.label for="auto_close_days">Auto-close resolved tickets (days)</x-form.label>
                        <x-form.input name="auto_close_days" type="number" :value="settings('auto_close_days')" />
                    </div>
                    <div>
                        <x-form.label for="audit_retention_days">Audit log retention (days) — 0 keeps forever</x-form.label>
                        <x-form.input name="audit_retention_days" type="number" :value="settings('audit_retention_days')" />
                    </div>
                    <div>
                        <x-form.label for="notification_retention_days">Notification retention (days) — 0 keeps forever</x-form.label>
                        <x-form.input name="notification_retention_days" type="number" :value="settings('notification_retention_days')" />
                    </div>
                    <div>
                        <x-form.label for="id_image_retention_days">ID document retention (days) — 0 keeps forever</x-form.label>
                        <x-form.input name="id_image_retention_days" type="number" :value="settings('id_image_retention_days')" />
                        <p class="text-xs text-ink-faint mt-1">After this many days the ID image file is deleted automatically. The ID number and the decision record are always kept.</p>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <x-button.primary type="submit" class="w-full justify-center">Save settings</x-button.primary>
            </x-card>

            <x-card title="Mail" description="Test or verify the outgoing mail configuration." flush>
                <div class="p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <form method="POST" action="{{ route('admin.settings.test-mail') }}">
                                @csrf
                                @php($mailButtonDisabled = ! $mailConfigured)
                                <x-button.secondary type="submit" :disabled="$mailButtonDisabled" class="w-full justify-center">
                                    {{ $mailConfigured ? 'Send test email' : 'Mail not configured' }}
                                </x-button.secondary>
                            </form>
                            <p class="text-xs text-ink-faint mt-2">
                                @if ($mailConfigured)
                                    A test email will be sent to you.
                                @else
                                    Set the MAIL_* values in your .env file to enable email notifications.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </form>
@endsection