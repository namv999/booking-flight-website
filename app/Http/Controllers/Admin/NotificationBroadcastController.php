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
        $search = trim((string) $request->input('search'));
        $type = $request->input('type');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $sort = $request->input('sort', 'latest');

        $broadcasts = NotificationBroadcast::with('admin')
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$search}%")->orWhere('message', 'like', "%{$search}%")
            ))
            ->when(array_key_exists((string) $type, AppNotification::BROADCAST_TYPES), fn ($q) => $q->where('type', $type))
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->when($sort === 'oldest', fn ($q) => $q->oldest('created_at')->oldest('id'))
            ->when($sort === 'recipients_desc', fn ($q) => $q->orderBy('recipient_count', 'desc')->latest('id'))
            ->when($sort === 'recipients_asc', fn ($q) => $q->orderBy('recipient_count', 'asc')->latest('id'))
            ->when($sort === 'title_asc', fn ($q) => $q->orderBy('title', 'asc'))
            ->when($sort === 'title_desc', fn ($q) => $q->orderBy('title', 'desc'))
            ->when(! in_array($sort, ['oldest', 'recipients_desc', 'recipients_asc', 'title_asc', 'title_desc'], true), fn ($q) => $q->latest()->latest('id'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.notification-broadcasts.index', [
            'broadcasts' => $broadcasts,
            'search' => $search,
            'type' => $type,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'sort' => $sort,
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
