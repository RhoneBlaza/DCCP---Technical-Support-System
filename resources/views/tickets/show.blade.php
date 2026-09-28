@extends('layouts.app')

@section('title', $ticket->ticket_number.' — '.Str::limit($ticket->subject, 40))

@php
    $user = auth()->user();
    $isStaff = $user->isStaff();
@endphp

@section('content')
    <x-page-header
        title="{{ $ticket->ticket_number }}"
        description="{{ $ticket->subject }}"
        breadcrumb="/ {{ $ticket->subject }}">
        <x-slot:actions>
            @if ($user->can('manage', $ticket) && ! $ticket->is_resolved && ! $ticket->is_closed)
                <form method="POST" action="{{ route('support.resolve', $ticket) }}" x-data="{ open: false }" @submit="open = false">
                    @csrf
                    @method('PUT')
                    <x-button.primary type="submit" class="hidden" x-show="open" x-cloak></x-button.primary>
                    <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700" @click="open = true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Resolve
                    </button>
                    <div x-show="open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                        <div class="absolute inset-0 bg-navy-950/60" @click="open = false"></div>
                        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-xl p-5">
                            <h3 class="text-base font-semibold text-slate-800 mb-1">Resolve ticket</h3>
                            <p class="text-sm text-slate-500 mb-4">Summarize the resolution for the requester.</p>
                            <x-form.label for="body" required>Resolution summary</x-form.label>
                            <x-form.textarea name="body" id="body" rows="4" required placeholder="How was this issue resolved?" />
                            <div class="flex items-center justify-end gap-2 mt-4">
                                <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100" @click="open = false">Cancel</button>
                                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700">Mark as resolved</button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif

            @if ($user->can('reopen', $ticket))
                <form method="POST" action="{{ route('support.reopen', $ticket) }}" x-data="{ open: false }">
                    @csrf
                    @method('PUT')
                    <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-orange-500 text-white text-sm font-medium hover:bg-orange-600" @click="open = true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reopen
                    </button>
                    <div x-show="open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                        <div class="absolute inset-0 bg-navy-950/60" @click="open = false"></div>
                        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-xl p-5">
                            <h3 class="text-base font-semibold text-slate-800 mb-1">Reopen ticket</h3>
                            <p class="text-sm text-slate-500 mb-4">Explain why this ticket is being reopened.</p>
                            <x-form.label for="body" required>Reason</x-form.label>
                            <x-form.textarea name="body" id="body" rows="4" required placeholder="Why is this ticket being reopened?" />
                            <div class="flex items-center justify-end gap-2 mt-4">
                                <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100" @click="open = false">Cancel</button>
                                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-orange-500 text-white hover:bg-orange-600">Reopen ticket</button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif

            @if ($user->can('close', $ticket))
                <form method="POST" action="{{ route('support.close', $ticket) }}">
                    @csrf
                    @method('PUT')
                    <x-button.secondary type="submit">Close</x-button.secondary>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-form.errors />

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6 min-w-0">
            <x-card flush>
                <div class="px-4 sm:px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center gap-2">
                    @if ($ticket->status)
                        <x-status-badge :status="$ticket->status" />
                    @endif
                    <x-priority-badge :priority="$ticket->priority" />
                    <x-sla-badge :ticket="$ticket" />
                    <span class="ml-auto text-xs text-slate-400"><x-safe-date :date="$ticket->created_at" /></span>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($messages as $message)
                        <div class="px-4 sm:px-5 py-4 {{ $message->is_internal ? 'bg-amber-50/40' : '' }}">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-navy-100 text-navy-800 text-xs font-semibold shrink-0">{{ $message->user?->initials ?? '?' }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-800">{{ $message->user?->full_name ?? 'Unknown' }}</span>
                                        @if ($message->user?->isStaff())
                                            <x-badge color="{{ $message->user->isAdmin() ? 'purple' : 'blue' }}">{{ $message->user->role->label() }}</x-badge>
                                        @else
                                            <x-badge color="slate">Requester</x-badge>
                                        @endif
                                        @if ($message->is_internal)
                                            <x-badge color="amber" dot>Internal note</x-badge>
                                        @endif
                                        <time class="text-xs text-slate-400" datetime="{{ $message->created_at->toIso8601String() }}" title="{{ $message->created_at->format('M j, Y H:i') }}">{{ $message->created_at->format('M j, Y') }}</time>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $message->body }}</div>

                                    @if ($message->attachments->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($message->attachments as $attachment)
                                                <a href="{{ route('tickets.download', [$ticket, $attachment]) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 ring-1 ring-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                    {{ $attachment->original_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 sm:px-5 py-4">
                            <x-empty-state title="No messages yet" description="There are no replies on this ticket yet." />
                        </div>
                    @endforelse
                </div>
            </x-card>

            @if ($user->can('reply', $ticket))
                <x-card title="Reply" description="Your message is visible to the requester">
                    <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <x-form.textarea name="body" rows="4" required placeholder="Type your reply…" value="{{ old('body') }}" />
                        <div>
                            <input type="file" name="attachments[]" multiple
                                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-navy-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-navy-700 hover:file:bg-navy-100">
                            <x-form.error name="attachments" />
                            <x-form.error name="attachments.*" />
                        </div>
                        <div class="flex items-center gap-3">
                            <x-button.primary type="submit">Post reply</x-button.primary>
                            <span class="text-xs text-slate-400">{{ settings('max_attachments_per_message', 5) }} files max · {{ settings('max_attachment_kb', 5120) }} KB each</span>
                        </div>
                    </form>
                </x-card>
            @endif

            @if ($isStaff)
                <x-card title="Internal note" description="Only support staff can see this. Requesters never see internal notes.">
                    <form method="POST" action="{{ route('tickets.internal-note', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <x-form.textarea name="body" rows="3" required placeholder="Visible to staff only…" value="{{ old('body') }}" />
                        <div>
                            <input type="file" name="attachments[]" multiple
                                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-amber-700 hover:file:bg-amber-100">
                            <x-form.error name="attachments" />
                            <x-form.error name="attachments.*" />
                        </div>
                        <x-button.secondary type="submit">Add internal note</x-button.secondary>
                    </form>
                </x-card>
            @endif
        </div>

        <div class="space-y-6 min-w-0">
            <x-card title="Details">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Requester</dt>
                        <dd class="text-slate-800 font-medium">{{ $ticket->requester?->full_name ?? '—' }}</dd>
                        @if ($ticket->requester?->department)
                            <dd class="text-xs text-slate-500">{{ $ticket->requester->department->name }}</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Assigned to</dt>
                        <dd class="text-slate-800">{{ $ticket->assignedUser?->full_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Department</dt>
                        <dd class="text-slate-800">{{ $ticket->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Category</dt>
                        <dd class="text-slate-800">{{ $ticket->category?->name ?? '—' }}</dd>
                    </div>
                    @if ($ticket->location)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Location</dt>
                            <dd class="text-slate-800">{{ $ticket->location }}</dd>
                        </div>
                    @endif
                    @if ($ticket->device_type)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Device</dt>
                            <dd class="text-slate-800">{{ $ticket->device_type }}</dd>
                        </div>
                    @endif
                    @if ($ticket->asset_number)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Asset / Serial</dt>
                            <dd class="text-slate-800 font-mono">{{ $ticket->asset_number }}</dd>
                        </div>
                    @endif
                    @if ($ticket->contact_number)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Contact</dt>
                            <dd class="text-slate-800">{{ $ticket->contact_number }}</dd>
                        </div>
                    @endif
                    <div class="pt-1 border-t border-slate-100">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Created</dt>
                        <dd class="text-slate-800">{{ $ticket->created_at?->format('M j, Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>

            @if ($isStaff)
                <x-card title="Manage" description="{{ $ticket->is_closed ? 'Closed tickets must be reopened before changes are allowed.' : 'Quick workflow actions' }}">
                    <div class="space-y-4">
                        @if ($user->can('assign', $ticket))
                            <form method="POST" action="{{ route('support.assign', $ticket) }}" class="flex items-end gap-2">
                                @csrf
                                @method('PUT')
                                <div class="flex-1">
                                    <x-form.label for="assignee_id">Assignee</x-form.label>
                                    <x-form.select name="assignee_id" :options="collect($staff)->mapWithKeys(fn ($s) => [$s->id => $s->full_name])" :selected="$ticket->assigned_to" placeholder="Unassigned" />
                                </div>
                                <x-button.primary type="submit">Assign</x-button.primary>
                            </form>
                            @if ($ticket->assigned_to)
                                <form method="POST" action="{{ route('support.unassign', $ticket) }}">
                                    @csrf
                                    @method('PUT')
                                    <x-button.secondary type="submit" class="w-full">Unassign</x-button.secondary>
                                </form>
                            @endif
                        @endif

                        @if ($user->can('changeStatus', $ticket))
                            <form method="POST" action="{{ route('support.update-status', $ticket) }}" class="flex items-end gap-2">
                                @csrf
                                @method('PUT')
                                <div class="flex-1">
                                    <x-form.label for="status_id">Status</x-form.label>
                                    <x-form.select name="status_id" :options="$statusChoices->mapWithKeys(fn ($s) => [$s->id => $s->name])" :selected="$ticket->status_id" />
                                </div>
                                <x-button.primary type="submit">Update</x-button.primary>
                            </form>
                        @endif

                        @if ($user->can('changePriority', $ticket))
                            <form method="POST" action="{{ route('support.update-priority', $ticket) }}" class="flex items-end gap-2">
                                @csrf
                                @method('PUT')
                                <div class="flex-1">
                                    <x-form.label for="priority_id">Priority</x-form.label>
                                    <x-form.select name="priority_id" :options="$priorities->mapWithKeys(fn ($p) => [$p->id => $p->name])" :selected="$ticket->priority_id" />
                                </div>
                                <x-button.primary type="submit">Update</x-button.primary>
                            </form>
                        @endif

                        @if ($user->can('changeCategory', $ticket))
                            <form method="POST" action="{{ route('support.update-category', $ticket) }}" class="flex items-end gap-2">
                                @csrf
                                @method('PUT')
                                <div class="flex-1">
                                    <x-form.label for="category_id">Category</x-form.label>
                                    <select name="category_id" id="category_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-navy-200 focus:border-navy-500">
                                        @foreach ($categories as $category)
                                            <optgroup label="{{ $category->name }}">
                                                @foreach ($category->children as $child)
                                                    <option value="{{ $child->id }}" @selected($ticket->category_id === $child->id)>{{ $child->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <x-button.primary type="submit">Update</x-button.primary>
                            </form>
                        @endif
                    </div>
                </x-card>
            @endif

            <x-card title="History" flush>
                <ol class="divide-y divide-slate-100">
                    @forelse ($ticket->activities as $activity)
                        <li class="px-4 sm:px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-800">{{ $activity->description }}</span>
                                @if ($activity->is_internal)
                                    <x-badge color="amber">Internal</x-badge>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">
                                {{ $activity->user?->full_name ?? 'System' }}
                                · <time datetime="{{ $activity->created_at->toIso8601String() }}" title="{{ $activity->created_at->format('M j, Y H:i') }}">{{ $activity->created_at->format('M j, Y H:i') }}</time>
                            </div>
                        </li>
                    @empty
                        <li class="p-4">
                            <p class="text-sm text-slate-500">No activity recorded yet.</p>
                        </li>
                    @endforelse
                </ol>
            </x-card>
        </div>
    </div>
@endsection