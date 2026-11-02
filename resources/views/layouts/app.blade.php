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
    <!-- CSS Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- JS Flatpickr + Tiếng Việt -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <a class="{{ request()->is('/') ? 'bf-navbar__link--active' : '' }}" href="{{ route('home') }}">Trang chủ</a>
                <a href="{{ route('home') }}#search-box">Tìm chuyến bay</a>
                @auth
                    <a class="{{ request()->routeIs('dashboard') ? 'bf-navbar__link--active' : '' }}" href="{{ route('dashboard') }}">Chuyến đi của tôi</a>
                    <a class="{{ request()->routeIs('saved-passengers.*') ? 'bf-navbar__link--active' : '' }}" href="{{ route('saved-passengers.index') }}">Hồ sơ hành khách</a>
                @endauth
                <a href="{{ route('home') }}#offers">Ưu đãi</a>
                <a href="tel:19001234">Hỗ trợ</a>
            </nav>

            <div class="bf-navbar__actions">
                @auth
                    <a class="bf-button bf-button--ghost d-none d-lg-inline-flex" href="{{ route('dashboard') }}">Chuyến đi của tôi</a>
                    <div class="dropdown">
                        <button class="bf-avatar border-0" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu tài khoản">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuDropdown">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-dark">{{ Auth::user()->name }}</div>
                                <div class="text-muted small text-truncate" style="max-width: 200px;">{{ Auth::user()->email }}</div>
                            </li>
                            @if (Auth::user()->role === 'admin')
                                <li>
                                    <a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-2 text-primary"></i>Trang quản trị
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                            @endif
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('dashboard') }}">
                                    <i class="bi bi-airplane me-2 text-primary"></i>Chuyến đi của tôi
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('booking-history.index') }}">
                                    <i class="bi bi-clock-history me-2 text-primary"></i>Lịch sử đặt vé
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('saved-passengers.index') }}">
                                    <i class="bi bi-people me-2 text-primary"></i>Hồ sơ hành khách
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person-gear me-2 text-primary"></i>Cài đặt tài khoản
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
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
                <a href="{{ route('home') }}#search-box">Vé máy bay</a>
                <a href="{{ route('home') }}#destinations">Điểm đến</a>
                <a href="{{ route('home') }}#offers">Ưu đãi mới</a>
            </div>
            <div>
                <h2>Dịch vụ & Hỗ trợ</h2>
                <a href="{{ route('dashboard') }}">Chuyến đi của tôi</a>
                <a href="{{ route('booking-history.index') }}">Lịch sử đặt vé</a>
                <a href="tel:19001234">Trung tâm trợ giúp 24/7</a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/layout.js'])
    @yield('scripts')
</body>
</html>