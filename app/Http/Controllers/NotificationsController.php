<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        if (! $request->wantsJson() && ! $request->ajax()) {
            return response()->json([]);
        }

        $user = auth()->user();

        $items = $user->notifications()->orderBy('created_at', 'desc')->limit(6)->get()->map(function ($n) {
            $data = $n->data;

            return [
                'id' => $n->id,
                'text' => $data['text'] ?? 'Notification',
                'url' => $data['url'] ?? null,
                'read_at' => $n->read_at?->toISOString(),
                'relative' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    public function markRead(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Defence in depth: only ever redirect to a local URL. Notification
        // payloads are server-generated today, but this keeps a future
        // user-influenced payload from becoming an open redirect.
        if (! is_string($url) || ! $this->isLocalUrl($url)) {
            return redirect()->route('notifications.index');
        }

        return redirect()->to($url);
    }

    protected function isLocalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === '') {
            return true;
        }

        return $host === parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
