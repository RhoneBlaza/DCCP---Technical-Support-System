@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <x-page-header
        title="Notifications"
        description="{{ $unreadCount > 0 ? $unreadCount.' unread notification'.($unreadCount === 1 ? '' : 's') : 'You are all caught up' }}">
        <x-slot:actions>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button.secondary type="submit">Mark all as read</x-button.secondary>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card flush class="mt-6">
        @if ($notifications->isEmpty())
            <x-empty-state title="No notifications" description="Notifications about your tickets will appear here." />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    <li class="flex items-start gap-3 px-4 sm:px-5 py-3.5 {{ $notification->read_at ? '' : 'bg-navy-50/40' }}">
                        <div class="min-w-0 flex-1">
                            @if (! $notification->read_at)
                                <span class="inline-block w-2 h-2 rounded-full bg-navy-500 mt-1.5 mr-2 align-middle"></span>
                            @endif
                            @if (! empty($notification->data['title']))
                                <p class="text-sm font-semibold text-slate-800">{{ $notification->data['title'] }}</p>
                            @endif
                            <p class="text-sm text-slate-700">{{ $notification->data['text'] ?? 'Notification' }}</p>
                            <p class="text-xs text-slate-400 mt-0.5" title="{{ $notification->created_at->format('M j, Y H:i') }}">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if ($notification->data['url'] ?? null)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <x-button.secondary type="submit" class="px-3 py-1.5 text-xs">View</x-button.secondary>
                                </form>
                            @elseif (! $notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <x-button.secondary type="submit" class="px-3 py-1.5 text-xs">Mark read</x-button.secondary>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($notifications->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection