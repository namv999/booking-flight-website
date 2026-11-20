<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->input('filter') === 'unread' ? 'unread' : 'all';

        $notifications = ($filter === 'unread' ? $user->unreadNotifications() : $user->notifications())
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function feed(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(8)->get()
                ->map(fn ($n) => $this->present($n))->all(),
        ])->header('Cache-Control', 'no-store');
    }

    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $target = $this->safeUrl($notification->data['url'] ?? null);

        if ($request->expectsJson()) {
            return response()->json([
                'url' => $target,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return redirect($target);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => 0]);
        }

        return back();
    }

    private function present(DatabaseNotification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->data['type'] ?? 'announcement',
            'title' => $n->data['title'] ?? '',
            'message' => $n->data['message'] ?? '',
            'url' => $this->safeUrl($n->data['url'] ?? null),
            'read' => $n->read_at !== null,
            'time' => $n->created_at->locale('vi')->diffForHumans(),
            'read_url' => route('notifications.read', $n->id, false),
        ];
    }

    private function safeUrl(?string $url): string
    {
        if ($url && preg_match('#^/(?!/)[^\s\\\\]*$#', $url)) {
            return $url;
        }

        return route('notifications.index', [], false);
    }
}
