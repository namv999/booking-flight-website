{{-- resources/views/layouts/app.blade.php --}}
{{-- LAYOUT: dùng chung cho MỌI trang user-facing (khách + user đã login) - navbar, footer, @yield('styles'/'content'/'scripts') --}}

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Jet Charter Flights')</title>
    <meta name="description" content="Đặt vé máy bay nội địa và quốc tế thuận tiện cùng Jet Charter Flights.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> -->
    @yield('styles')
</head>
<body class="bf-body">
    <header class="bf-navbar" id="bf-main-header">
        <div class="bf-container bf-navbar__inner">
            <a class="bf-brand" href="/" aria-label="Jet Charter Flights - Trang chủ">
                <span class="bf-brand__mark" aria-hidden="true">JET</span>
                <span class="bf-brand__copy">
                    <strong>Jet Charter</strong>
                    <small>Flights & journeys</small>
                </span>
            </a>

            <button class="bf-icon-button bf-navbar__toggle" id="bf-mobile-menu-toggle" type="button"
                aria-controls="bf-main-navigation" aria-expanded="false" aria-label="Mở trình đơn">
                <span></span><span></span><span></span>
            </button>

            <nav class="bf-navbar__nav" id="bf-main-navigation" aria-label="Điều hướng chính">
                <a class="{{ request()->is('/') ? 'bf-navbar__link--active' : '' }}" href="/">Trang chủ</a>
                <a href="#">Chuyến bay</a>
                <a href="#">Khuyến mãi</a>
                <a href="#">Hỗ trợ</a>
            </nav>

            <div class="bf-navbar__actions">
                @auth
                    <a class="bf-button bf-button--ghost" href="{{ route('dashboard') }}">Chuyến đi của tôi</a>
                    <a class="bf-avatar" href="{{ route('profile.edit') }}" aria-label="Mở hồ sơ cá nhân">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </a>
                @else
                    <a class="bf-button bf-button--ghost" href="{{ route('login') }}">Đăng nhập</a>
                    <a class="bf-button bf-button--primary" href="{{ route('register') }}">Tạo tài khoản</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="bf-main">
        @yield('content')
    </main>

    <footer class="bf-footer">
        <div class="bf-container bf-footer__grid">
            <div>
                <a class="bf-brand bf-brand--footer" href="/">
                    <span class="bf-brand__mark" aria-hidden="true">JET</span>
                    <span class="bf-brand__copy"><strong>Jet Charter</strong><small>Flights & journeys</small></span>
                </a>
                <p>Đồng hành trên mọi hành trình với lựa chọn rõ ràng và hỗ trợ tận tâm.</p>
            </div>
            <div>
                <h2>Khám phá</h2>
                <a href="#">Vé máy bay</a>
                <a href="#">Điểm đến</a>
                <a href="#">Ưu đãi mới</a>
            </div>
            <div>
                <h2>Hỗ trợ</h2>
                <a href="#">Trung tâm trợ giúp</a>
                <a href="#">Điều khoản sử dụng</a>
                <a href="#">Chính sách bảo mật</a>
            </div>
            <div>
                <h2>Liên hệ</h2>
                <strong class="bf-footer__hotline">1900 1234</strong>
                <span>Hỗ trợ 24/7, kể cả ngày lễ</span>
            </div>
        </div>
        <div class="bf-container bf-footer__bottom">
            <span>© 2026 Jet Charter Flights</span>
            <span>Bay thuận tiện, đi trọn niềm vui.</span>
        </div>
    </footer>

    <!-- <script src="{{ asset('js/layout.js') }}"></script> -->
    @vite(['resources/js/layout.js'])
    @yield('scripts')
</body>
</html>
