@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.app')

@section('title', 'Thông báo - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0 bf-notifications-page">
    <div class="bf-notifications-heading">
        <div>
            <span class="bf-eyebrow">Cập nhật hành trình và tài khoản</span>
            <h1>Thông báo</h1>
            <p>Theo dõi các cập nhật mới nhất của bạn.</p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-check2-all me-1"></i>Đánh dấu tất cả đã đọc</button>
            </form>
        @endif
    </div>

    <nav class="bf-notifications-tabs" aria-label="Lọc thông báo">
        <a class="{{ $filter === 'all' ? 'is-active' : '' }}" href="{{ route('notifications.index') }}" @if ($filter === 'all') aria-current="page" @endif>Tất cả</a>
        <a class="{{ $filter === 'unread' ? 'is-active' : '' }}" href="{{ route('notifications.index', ['filter' => 'unread']) }}" @if ($filter === 'unread') aria-current="page" @endif>
            Chưa đọc <span>{{ $unreadCount }}</span>
        </a>
    </nav>

    <div class="bf-notifications-list">
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
        @endphp
        @forelse ($notifications as $n)
            @php
                $kind = $n->data['type'] ?? '';
                $typeMeta = $notificationTypes[$kind] ?? $fallbackNotification;
            @endphp
            <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="bf-notif-item-form" data-notif-read-form data-read-url="{{ route('notifications.read', $n->id, false) }}">
                @csrf
                <button type="submit" class="bf-notif-item bf-notif-item--page {{ $n->read_at ? 'is-read' : 'is-unread' }}">
                    <span class="bf-notif-item__icon {{ $typeMeta['class'] }}"><i class="bi {{ $typeMeta['icon'] }}" aria-hidden="true"></i></span>
                    <span class="bf-notif-item__copy">
                        <span class="bf-notif-item__title">{{ $n->data['title'] ?? '' }}
                            @if (! $n->read_at) <span class="bf-notif-new-label">Mới</span> @endif
                            <span class="bf-notif-unread-dot" aria-label="Chưa đọc"></span>
                        </span>
                        <span class="bf-notif-item__message bf-notif-item__message--full">{{ $n->data['message'] ?? '' }}</span>
                        <span class="bf-notif-item__time" title="{{ $n->created_at->format('d/m/Y H:i') }}">{{ $n->created_at->locale('vi')->diffForHumans() }}</span>
                    </span>
                </button>
            </form>
        @empty
            <div class="bf-notifications-empty">
                <span class="bf-notifications-empty__icon"><i class="bi bi-bell-slash" aria-hidden="true"></i></span>
                @if ($filter === 'unread')
                    <h2>Bạn đã đọc hết thông báo</h2>
                    <p>Không còn thông báo chưa đọc.</p>
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary">Xem tất cả</a>
                @else
                    <h2>Chưa có thông báo nào</h2>
                    <p>Khi có cập nhật mới, thông báo sẽ xuất hiện tại đây.</p>
                @endif
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="d-flex justify-content-center mt-4">{{ $notifications->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
