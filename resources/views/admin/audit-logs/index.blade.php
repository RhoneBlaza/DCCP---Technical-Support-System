@extends('layouts.app')

@section('title', 'Activity monitor')

@section('content')
    <x-page-header
        title="Activity monitor"
        description="Append-only feed of logins, registrations, ID views, approvals and ticket activity. Refreshes every 15 seconds.">
        <x-slot:actions>
            <x-button.secondary :href="route('admin.audit-logs.export', request()->query())">Export CSV</x-button.secondary>
        </x-slot:actions>
    </x-page-header>

    <x-card flush class="mt-6">
        <div class="p-4 sm:p-5 border-b border-line-soft">
            <x-list-search
                :route="route('admin.audit-logs.index')"
                placeholder="Search by ticket number, subject or IP"
                :reset-keys="['user', 'action', 'ticket', 'from', 'to', 'ip', 'model']">
                <div class="w-44">
                    <x-form.label for="user">User</x-form.label>
                    <x-form.select name="user" title="Applied automatically" onchange="this.form.requestSubmit()" :options="$users->pluck('full_name', 'id')->prepend('All users', '')" :selected="request('user')" />
                </div>

                <div class="w-44">
                    <x-form.label for="action">Action</x-form.label>
                    <x-form.select name="action" title="Applied automatically" onchange="this.form.requestSubmit()" :options="collect($actions)->mapWithKeys(fn ($a) => [$a => $a])->prepend('All actions', '')" :selected="request('action')" />
                </div>

                <div class="w-40">
                    <x-form.label for="ticket">Ticket #</x-form.label>
                    <x-form.input name="ticket" :value="request('ticket')" placeholder="e.g. DCCP-000021" />
                </div>

                <div class="w-40">
                    <x-form.label for="ip">IP address</x-form.label>
                    <x-form.input name="ip" :value="request('ip')" placeholder="e.g. 127.0.0.1" />
                </div>

                <div class="w-40">
                    <x-form.label for="from">From</x-form.label>
                    <x-form.input name="from" type="date" :value="request('from')" />
                </div>

                <div class="w-40">
                    <x-form.label for="to">To</x-form.label>
                    <x-form.input name="to" type="date" :value="request('to')" />
                </div>
            </x-list-search>

            <div class="mt-3 flex items-center gap-2 text-xs text-ink-subtle" x-data="{ lastRefresh: '' }" x-init="lastRefresh = new Date().toLocaleTimeString()">
                <span class="inline-flex w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Live — last refreshed <span class="live-badge" x-text="lastRefresh"></span>
            </div>
        </div>

        <div
            class="overflow-x-auto"
            x-data="{ async refresh() { try { const res = await fetch('{{ $feedUrl }}'); const html = await res.text(); document.getElementById('activity-rows').innerHTML = html; } catch (e) {} const badge = document.querySelector('[x-data] .live-badge'); if (badge) badge.textContent = new Date().toLocaleTimeString(); } }"
            x-init="setInterval(() => refresh(), 15000)">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-subtle border-b border-line bg-surface-muted/80">
                        <th class="px-4 py-2.5">When</th>
                        <th class="px-4 py-2.5">User</th>
                        <th class="px-4 py-2.5">Action</th>
                        <th class="px-4 py-2.5">Details</th>
                        <th class="px-4 py-2.5">IP</th>
                    </tr>
                </thead>
                <tbody id="activity-rows" class="bg-surface divide-y divide-line-soft">
                    @include('admin.audit-logs.partials.rows', ['logs' => $logs])
                </tbody>
            </table>
        </div>
    </x-card>

    <p class="mt-4 text-xs text-ink-faint">
        To view a user's or a ticket's full history, filter by <strong>User</strong> or <strong>Ticket #</strong> above.
        Audit logs are append-only and can never be edited or deleted from the application.
    </p>
@endsection