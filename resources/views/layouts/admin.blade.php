<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị - Jet Charter Flights')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bf-body bf-admin-body">
    <div class="bf-admin-shell">
        <aside class="bf-admin-sidebar" id="bf-admin-sidebar">
            <a class="bf-brand bf-brand--admin" href="/">
                <span class="bf-brand__mark" aria-hidden="true">JET</span>
                <span class="bf-brand__copy"><strong>Jet Charter</strong><small>Admin workspace</small></span>
            </a>
            <nav class="bf-admin-nav" aria-label="Điều hướng quản trị">
                <a class="bf-admin-nav__link--active" href="#"><span aria-hidden="true">01</span>Tổng quan</a>
                <a href="#"><span aria-hidden="true">02</span>Chuyến bay</a>
                <a href="#"><span aria-hidden="true">03</span>Đặt vé</a>
                <a href="#"><span aria-hidden="true">04</span>Khách hàng</a>
                <a href="#"><span aria-hidden="true">05</span>Khuyến mãi</a>
                <a href="#"><span aria-hidden="true">06</span>Báo cáo</a>
            </nav>
            <div class="bf-admin-sidebar__support">
                <strong>Cần hỗ trợ?</strong>
                <p>Liên hệ nhóm kỹ thuật nội bộ.</p>
                <a href="#">Mở trung tâm hỗ trợ</a>
            </div>
        </aside>

        <div class="bf-admin-main">
            <header class="bf-admin-topbar">
                <button class="bf-icon-button bf-admin-toggle" id="bf-admin-menu-toggle" type="button"
                    aria-controls="bf-admin-sidebar" aria-expanded="false" aria-label="Mở thanh điều hướng">
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <span class="bf-eyebrow">Không gian quản trị</span>
                    <strong>Điều hành chuyến bay</strong>
                </div>
                <div class="bf-admin-topbar__actions">
                    <button class="bf-icon-button bf-notification-button" type="button" aria-label="Thông báo">
                        <span aria-hidden="true">!</span>
                    </button>
                    <a class="bf-avatar" href="#" aria-label="Tài khoản quản trị">A</a>
                </div>
            </header>
            <main class="bf-admin-content">
                @yield('content')
            </main>
        </div>
    </div>
    <script src="{{ asset('js/layout.js') }}"></script>
    @yield('scripts')
</body>
</html>
