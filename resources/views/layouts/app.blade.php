{{-- resources/views/layouts/app.blade.php --}}
{{-- LAYOUT: dùng chung cho MỌI trang user-facing (khách + user đã login) - navbar, footer, @yield('styles'/'content'/'scripts') --}}

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Jet Charter Flights')</title>
    <meta name="description" content="@yield('meta_description', 'Đặt vé máy bay nội địa và quốc tế thuận tiện cùng Jet Charter Flights.')">
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
    @auth
        @vite(['resources/css/notifications.css'])
    @endauth
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
                <a href="{{ route('home') }}#offers">Ưu đãi</a>
                <a class="{{ request()->routeIs('news.*') ? 'bf-navbar__link--active' : '' }}" href="{{ route('news.index') }}">Tin tức</a>
                <a href="tel:19001234">Hỗ trợ</a>
            </nav>

            <div class="bf-navbar__actions">
                @auth
                <a class="bf-button bf-button--ghost d-none d-lg-inline-flex" href="{{ route('dashboard') }}">
                    Chuyến đi của tôi
                </a>
                @include('partials.notification-bell')
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
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
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
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
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
                <a href="{{ route('news.index') }}">Tin tức & cẩm nang</a>
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

    {{-- FLOATING ACTION BUTTON (QUICK SUPPORT & CONTACT) --}}
    <div class="bf-fab-container" id="bf-fab-container">
        {{-- Popover menu --}}
        <div class="bf-fab-panel" id="bf-fab-panel" aria-hidden="true">
            <div class="bf-fab-panel__header">
                <div class="d-flex align-items-center gap-2">
                    <div class="bf-fab-avatar">
                        <i class="bi bi-headset"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-white" style="font-size: 0.98rem; letter-spacing: 0.2px;">Hỗ trợ Jet Charter</h6>
                        <small class="d-flex align-items-center gap-1 text-white" style="font-size: 0.75rem; font-weight: 500;">
                            <span class="bf-status-dot"></span> Đang trực tuyến 24/7
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white btn-sm" id="bf-fab-close" aria-label="Đóng"></button>
            </div>
            <div class="bf-fab-panel__body">
                <a href="tel:19001234" class="bf-fab-action-item">
                    <div class="bf-fab-action-icon bg-success-subtle text-success">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div class="bf-fab-action-text">
                        <div class="fw-bold text-dark">1900 1234</div>
                        <div class="bf-fab-action-sub">Tổng đài đặt vé & hỗ trợ 24/7</div>
                    </div>
                    <i class="bi bi-chevron-right text-muted ms-auto"></i>
                </a>

                <a href="https://m.me" target="_blank" rel="noopener noreferrer" class="bf-fab-action-item">
                    <div class="bf-fab-action-icon bg-primary-subtle text-primary">
                        <i class="bi bi-messenger"></i>
                    </div>
                    <div class="bf-fab-action-text">
                        <div class="fw-bold text-dark">Facebook Messenger</div>
                        <div class="bf-fab-action-sub">Chat trực tiếp cùng tư vấn viên</div>
                    </div>
                    <i class="bi bi-box-arrow-up-right text-muted ms-auto"></i>
                </a>

                <a href="https://zalo.me" target="_blank" rel="noopener noreferrer" class="bf-fab-action-item">
                    <div class="bf-fab-action-icon bg-info-subtle text-info">
                        <i class="bi bi-chat-dots-fill"></i>
                    </div>
                    <div class="bf-fab-action-text">
                        <div class="fw-bold text-dark">Zalo Official</div>
                        <div class="bf-fab-action-sub">Nhận ưu đãi & giải đáp thắc mắc</div>
                    </div>
                    <i class="bi bi-box-arrow-up-right text-muted ms-auto"></i>
                </a>

                <button type="button" class="bf-fab-action-item w-100 border-0 bg-transparent text-start" data-bs-toggle="modal" data-bs-target="#bfQuickContactModal">
                    <div class="bf-fab-action-icon bg-warning-subtle text-warning">
                        <i class="bi bi-envelope-paper-fill"></i>
                    </div>
                    <div class="bf-fab-action-text">
                        <div class="fw-bold text-dark">Gửi lời nhắn hỗ trợ</div>
                        <div class="bf-fab-action-sub">Để lại thông tin, chúng tôi gọi lại ngay</div>
                    </div>
                    <i class="bi bi-chevron-right text-muted ms-auto"></i>
                </button>
            </div>
        </div>

        {{-- Main FAB Button --}}
        <button type="button" class="bf-fab-btn" id="bf-fab-toggle" aria-expanded="false" aria-label="Hỗ trợ khách hàng">
            <span class="bf-fab-icon bf-fab-icon--chat"><i class="bi bi-chat-dots-fill"></i></span>
            <span class="bf-fab-icon bf-fab-icon--close"><i class="bi bi-x-lg"></i></span>
            <span class="bf-fab-badge" aria-hidden="true"></span>
            <span class="bf-fab-pulse"></span>
            <span class="bf-fab-tooltip">Hỗ trợ 24/7</span>
        </button>
    </div>

    {{-- Quick Contact Modal --}}
    <div class="modal fade" id="bfQuickContactModal" tabindex="-1" aria-labelledby="bfQuickContactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-primary text-white px-4 py-3 border-0">
                    <h5 class="modal-title fw-bold" id="bfQuickContactModalLabel">
                        <i class="bi bi-envelope-fill me-2"></i>Gửi yêu cầu hỗ trợ nhanh
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Vui lòng để lại số điện thoại hoặc email. Đội ngũ chăm sóc khách hàng Jet Charter sẽ liên hệ lại trong vòng 15 phút.
                    </p>
                    <form id="bf-quick-contact-form" onsubmit="event.preventDefault(); Swal.fire({icon: 'success', title: 'Đã gửi yêu cầu!', text: 'Cảm ơn bạn! Đội ngũ tư vấn Jet Charter sẽ liên hệ lại sớm nhất.', confirmButtonColor: '#1268b3'}); bootstrap.Modal.getInstance(document.getElementById('bfQuickContactModal')).hide();">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm rounded-3" placeholder="Ví dụ: Nguyễn Văn A" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Số điện thoại hoặc Email <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm rounded-3" placeholder="0901 234 567 hoặc email@domain.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nội dung cần hỗ trợ <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm rounded-3" rows="3" placeholder="Nội dung bạn cần trợ giúp (đổi vé, hành lý, thanh toán...)" required></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">Gửi yêu cầu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- <script src="{{ asset('js/layout.js') }}"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/layout.js'])
    @auth
        @vite(['resources/js/notifications.js'])
    @endauth
    @yield('scripts')
</body>

</html>
