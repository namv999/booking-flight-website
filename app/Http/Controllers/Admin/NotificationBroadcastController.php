<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNotificationBroadcastRequest;
use App\Models\NotificationBroadcast;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationBroadcastController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $broadcasts = NotificationBroadcast::with('admin')
            ->when($search, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('message', 'like', "%{$s}%")
            ))
            ->when(array_key_exists((string) $type, AppNotification::BROADCAST_TYPES), fn ($q) => $q->where('type', $type))
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.notification-broadcasts.index', [
            'broadcasts' => $broadcasts,
            'search' => $search,
            'type' => $type,
            'types' => AppNotification::BROADCAST_TYPES,
        ]);
    }

    public function create()
    {
        return view('admin.notification-broadcasts.create', [
            'types' => AppNotification::BROADCAST_TYPES,
            'customerCount' => User::where('role', 'user')->count(),
        ]);
    }

    public function store(StoreNotificationBroadcastRequest $request, NotificationService $service)
    {
        $data = $request->validated();

        $broadcast = $service->broadcast(
            $request->user(),
            $data['type'],
            $data['title'],
            $data['message'],
            $data['url'] ?? null
        );

        return redirect()->route('admin.notification-broadcasts.index')
            ->with('success', "Đã gửi thông báo tới {$broadcast->recipient_count} khách hàng.");
    }
}
