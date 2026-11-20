@php
    $notificationTypes = config('notifications.types', [
        'passport_submitted' => ['icon' => 'bi-person-vcard', 'class' => 'bf-notif-icon--warning'],
        'passport_approved' => ['icon' => 'bi-check-circle-fill', 'class' => 'bf-notif-icon--success'],
        'passport_rejected' => ['icon' => 'bi-x-circle-fill', 'class' => 'bf-notif-icon--danger'],
        'booking_paid' => ['icon' => 'bi-ticket-perforated', 'class' => 'bf-notif-icon--primary'],
        'order_paid' => ['icon' => 'bi-receipt', 'class' => 'bf-notif-icon--primary'],
        'promotion' => ['icon' => 'bi-megaphone-fill', 'class' => 'bf-notif-icon--coral'],
        'announcement' => ['icon' => 'bi-info-circle-fill', 'class' => 'bf-notif-icon--info'],
    ]);
    $fallbackNotification = config('notifications.fallback', [
        'icon' => 'bi-bell',
        'class' => 'bf-notif-icon--muted',
    ]);
    $isAdminLayout = ($layout ?? null) === 'admin'
        || request()->is('admin', 'admin/*')
        || (auth()->check() && auth()->user()->role === 'admin' && request()->routeIs('notifications.*'));
@endphp

<div class="dropdown bf-notif" id="bf-notif"
     data-feed-url="{{ route('notifications.feed', [], false) }}"
     data-read-all-url="{{ route('notifications.read-all', [], false) }}"
     data-poll-ms="30000">
    <button type="button"
            class="bf-notif-toggle {{ $isAdminLayout ? 'bf-icon-button bf-notification-button' : 'bf-avatar bf-notif-user-toggle' }}"
            id="bf-notif-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
            aria-expanded="false" aria-label="Thông báo">
        <i class="bi bi-bell" aria-hidden="true"></i>
        <span class="bf-notif-badge" aria-live="polite" @if ($bellUnread < 1) hidden @endif>
            {{ $bellUnread > 99 ? '99+' : $bellUnread }}
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end bf-notif-menu" aria-labelledby="bf-notif-toggle">
        <div class="bf-notif-menu__header">
            <strong>Thông báo</strong>
            <form method="POST" action="{{ route('notifications.read-all') }}" id="bf-notif-read-all-form" @if ($bellUnread < 1) hidden @endif>
                @csrf
                <button type="submit" class="bf-notif-read-all" id="bf-notif-read-all">Đánh dấu tất cả đã đọc</button>
            </form>
        </div>

        <div class="bf-notif-list" id="bf-notif-list" aria-live="polite">
            @forelse ($bellItems as $n)
                @php
                    $kind = $n->data['type'] ?? '';
                    $typeMeta = $notificationTypes[$kind] ?? $fallbackNotification;
                @endphp
                <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="bf-notif-item-form" data-notif-read-form data-read-url="{{ route('notifications.read', $n->id, false) }}" style="--bf-notif-index: {{ $loop->index }};">
                    @csrf
                    <button type="submit" class="bf-notif-item {{ $n->read_at ? 'is-read' : 'is-unread' }}">
                        <span class="bf-notif-item__icon {{ $typeMeta['class'] }}"><i class="bi {{ $typeMeta['icon'] }}" aria-hidden="true"></i></span>
                        <span class="bf-notif-item__copy">
                            <span class="bf-notif-item__title">{{ $n->data['title'] ?? '' }}<span class="bf-notif-unread-dot" @if (! $n->read_at) aria-label="Chưa đọc" @endif></span></span>
                            <span class="bf-notif-item__message">{{ $n->data['message'] ?? '' }}</span>
                            <span class="bf-notif-item__time">{{ $n->created_at->locale('vi')->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="bf-notif-empty"><i class="bi bi-bell-slash" aria-hidden="true"></i><span>Chưa có thông báo nào</span></div>
            @endforelse
        </div>

        <div class="bf-notif-menu__footer">
            <a href="{{ route('notifications.index') }}">Xem tất cả</a>
        </div>
    </div>
</div>
