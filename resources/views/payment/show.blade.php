@extends('layouts.app')

@section('title', 'Thanh toán đơn đặt vé #' . $booking->id . ' - Jet Charter')

@section('styles')
@vite(['resources/css/flights.css'])
@endsection

@section('content')
<div class="bf-results-page">
    {{-- 1. THANH TIẾN TRÌNH 5 BƯỚC (BOOKING PROGRESS WIZARD) --}}
    <div class="bf-step-wizard">
        <div class="container">
            <ol class="bf-steps">
                <li class="bf-step-item completed">
                    <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                    <span class="d-none d-sm-inline">Tìm kiếm</span>
                </li>
                <li class="bf-step-divider completed"></li>
                <li class="bf-step-item completed">
                    <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                    <span class="d-none d-sm-inline">Chọn chuyến bay</span>
                </li>
                <li class="bf-step-divider completed"></li>
                <li class="bf-step-item completed">
                    <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                    <span class="d-none d-sm-inline">Thông tin khách</span>
                </li>
                <li class="bf-step-divider completed"></li>
                <li class="bf-step-item active" aria-current="step">
                    <span class="bf-step-circle">4</span>
                    <span>Thanh toán</span>
                </li>
                <li class="bf-step-divider"></li>
                <li class="bf-step-item">
                    <span class="bf-step-circle">5</span>
                    <span class="d-none d-sm-inline">Hoàn tất</span>
                </li>
            </ol>
        </div>
    </div>

    <div class="container">
        {{-- THÔNG BÁO FLASH / VALIDATION ERRORS --}}
        @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
        @endif

        @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0 fs-5"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
        @endif

        @if (isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>Vui lòng kiểm tra lại:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
        @endif

        <div class="row g-4">
            {{-- CỘT TRÁI: KHU VỰC CHỌN PHƯƠNG THỨC & MÔ PHỎNG THANH TOÁN --}}
            <main class="col-lg-8 col-md-7">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h1 class="h4 fw-bold text-navy mb-1" style="color: var(--bf-navy);">Thanh toán đơn đặt vé</h1>
                        <p class="text-muted small mb-0">Mã đơn hàng: <strong class="text-primary">#BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</strong> &bull; Đặt lúc {{ $booking->created_at->format('H:i, d/m/Y') }}</p>
                    </div>
                </div>

                {{-- BANNER THỜI HẠN THANH TOÁN (20 PHÚT) --}}
                <div class="bf-payment-expiry-banner" id="payment-expiry-box"
                     data-expires-at="{{ $expiresAt->timestamp }}"
                     data-server-now="{{ now()->timestamp }}"
                     data-redirect-url="{{ route('home') }}">
                    <div class="bf-payment-expiry-icon" aria-hidden="true">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="bf-payment-expiry-content">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="bf-payment-expiry-title">
                                Thời hạn hoàn tất thanh toán: {{ $expiresAt->format('H:i:s - d/m/Y') }}
                            </div>
                            <span class="bf-payment-countdown-badge" id="payment-countdown-badge">--:--</span>
                        </div>
                        <p class="bf-payment-expiry-desc mt-1">
                            Ghế của bạn đã được giữ trong 20 phút. Vui lòng hoàn tất thanh toán trước thời điểm trên.
                        </p>
                    </div>
                </div>

                {{-- FORM THANH TOÁN & GIẢ LẬP KẾT QUẢ --}}
                <form method="POST" action="{{ route('payment.store', $booking) }}" id="bf-payment-form">
                    @csrf

                    <div class="bf-payment-card">
                        <div class="bf-payment-card__header">
                            <h2 class="bf-payment-card__title">
                                <i class="bi bi-credit-card-2-front-fill text-primary" aria-hidden="true"></i>
                                <span>Chọn phương thức thanh toán</span>
                            </h2>
                            <span class="badge bg-light text-secondary border">Mã hóa an toàn</span>
                        </div>

                        {{-- Danh sách phương thức thanh toán --}}
                        <div class="bf-payment-methods">
                            {{-- Phương thức 1: Thẻ tín dụng --}}
                            <div class="bf-payment-method-item {{ old('method', 'the_tin_dung') === 'the_tin_dung' ? 'active' : '' }}">
                                <label class="bf-payment-method-label" for="method_card">
                                    <div class="bf-payment-method-left">
                                        <input class="form-check-input mt-0"
                                            type="radio"
                                            name="method"
                                            id="method_card"
                                            value="the_tin_dung"
                                            {{ old('method', 'the_tin_dung') === 'the_tin_dung' ? 'checked' : '' }}>
                                        <div class="bf-payment-method-info">
                                            <strong>Thẻ tín dụng / Ghi nợ quốc tế</strong>
                                            <small>Hỗ trợ thẻ thanh toán phát hành trong và ngoài nước (Visa, Mastercard, JCB)</small>
                                        </div>
                                    </div>
                                    <div class="bf-payment-method-icons d-none d-sm-flex">
                                        <span class="bf-payment-brand-badge">VISA</span>
                                        <span class="bf-payment-brand-badge">Mastercard</span>
                                        <span class="bf-payment-brand-badge">JCB</span>
                                    </div>
                                </label>
                            </div>

                            {{-- Phương thức 2: Ví điện tử --}}
                            <div class="bf-payment-method-item {{ old('method') === 'vi_dien_tu' ? 'active' : '' }}">
                                <label class="bf-payment-method-label" for="method_wallet">
                                    <div class="bf-payment-method-left">
                                        <input class="form-check-input mt-0"
                                            type="radio"
                                            name="method"
                                            id="method_wallet"
                                            value="vi_dien_tu"
                                            {{ old('method') === 'vi_dien_tu' ? 'checked' : '' }}>
                                        <div class="bf-payment-method-info">
                                            <strong>Ví điện tử & Cổng thanh toán</strong>
                                            <small>Quét mã QR hoặc đăng nhập thanh toán qua ví MoMo, ZaloPay, VNPay</small>
                                        </div>
                                    </div>
                                    <div class="bf-payment-method-icons d-none d-sm-flex">
                                        <span class="bf-payment-brand-badge">MoMo</span>
                                        <span class="bf-payment-brand-badge">ZaloPay</span>
                                        <span class="bf-payment-brand-badge">VNPay</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Khối mô phỏng kết quả thanh toán (Test Simulation) --}}
                        <div class="bf-simulation-box">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span class="bf-simulation-badge">
                                    <i class="bi bi-gear-fill me-1"></i> Môi trường mô phỏng (Testing Mode)
                                </span>
                                <small class="text-muted fst-italic">Mô phỏng đồ án &bull; Không trừ tiền tài khoản</small>
                            </div>
                            <p class="bf-simulation-desc">
                                Hệ thống đang chạy ở chế độ thử nghiệm đồ án. Bạn có thể chủ động chọn kết quả xử lý giao dịch bằng một trong hai nút bên dưới:
                            </p>

                            <div class="bf-simulation-actions">
                                <button type="submit" name="simulate_result" value="success" class="bf-btn-pay-success">
                                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                    <span>Giả lập THÀNH CÔNG</span>
                                </button>

                                <button type="submit" name="simulate_result" value="failed" class="bf-btn-pay-failed">
                                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                                    <span>Giả lập THẤT BẠI</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3 mb-5">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-shield-lock-fill text-success me-1"></i> Giao dịch được bảo mật và mã hóa an toàn theo tiêu chuẩn SSL 256-bit của Jet Charter Flights.
                        </small>
                    </div>
                </form>
            </main>

            {{-- CỘT PHẢI: SIDEBAR TÓM TẮT ĐƠN HÀNG & CHI TIẾT VÉ --}}
            <aside class="col-lg-4 col-md-5">
                @php
                $firstBf = $booking->bookingFlights->first();
                $flight = $firstBf?->flight;
                $depTime = $flight ? \Carbon\Carbon::parse($flight->departure_time) : null;
                $arrTime = $flight ? \Carbon\Carbon::parse($flight->arrival_time) : null;
                $diffMinutes = ($depTime && $arrTime) ? $depTime->diffInMinutes($arrTime) : 0;
                $hours = intdiv($diffMinutes, 60);
                $mins = $diffMinutes % 60;
                $durationText = ($hours > 0 ? "{$hours}h " : "") . ($mins > 0 ? "{$mins}m" : "00m");
                $airlineName = $flight?->aircraft?->airline?->name ?? 'Hãng hàng không';
                $aircraftModel = $flight?->aircraft?->model ?? 'Aircraft';
                $departureAirport = $flight?->departureAirport;
                $arrivalAirport = $flight?->arrivalAirport;
                $totalTicketsCount = 0;
                foreach ($booking->bookingFlights as $bf) {
                $totalTicketsCount += $bf->tickets->count();
                }
                @endphp

                <div class="bf-booking-sidebar">
                    <h3 class="bf-booking-sidebar__title">
                        <i class="bi bi-receipt text-primary" aria-hidden="true"></i>
                        <span>Tóm tắt đơn hàng</span>
                    </h3>

                    @if ($flight)
                    <div class="bf-sidebar-route">
                        <div class="bf-sidebar-route-item">
                            <strong>{{ $departureAirport?->iata_code ?? $flight->departure_airport_id }}</strong>
                            <small>{{ $departureAirport?->city ?? 'Sân bay đi' }}</small>
                        </div>
                        <i class="bi bi-arrow-right bf-sidebar-arrow" aria-hidden="true"></i>
                        <div class="bf-sidebar-route-item text-end">
                            <strong>{{ $arrivalAirport?->iata_code ?? $flight->arrival_airport_id }}</strong>
                            <small>{{ $arrivalAirport?->city ?? 'Sân bay đến' }}</small>
                        </div>
                    </div>

                    <div class="bf-sidebar-airline">
                        <i class="bi bi-airplane" aria-hidden="true"></i>
                        <div>
                            <div class="fw-bold text-navy">{{ $airlineName }}</div>
                            <div class="small text-muted">{{ $aircraftModel }} &bull; Chuyến #FL-{{ str_pad($flight->id, 4, '0', STR_PAD_LEFT) }}</div>
                        </div>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Cất cánh:</span>
                        <strong>{{ $depTime?->format('H:i, d/m/Y') }}</strong>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Hạ cánh:</span>
                        <strong>{{ $arrTime?->format('H:i, d/m/Y') }}</strong>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Thời gian bay:</span>
                        <span>{{ $durationText }} &bull; Bay thẳng</span>
                    </div>
                    @endif

                    {{-- DANH SÁCH CHI TIẾT VÉ HÀNH KHÁCH --}}
                    <div class="bf-ticket-breakdown-list">
                        <div class="bf-ticket-breakdown-title">
                            <span>Chi tiết vé hành khách</span>
                            <span class="text-muted small">({{ $totalTicketsCount }} vé)</span>
                        </div>

                        @foreach ($booking->bookingFlights as $bf)
                        @foreach ($bf->tickets as $ticket)
                        @php
                        $pType = $ticket->passenger->passenger_type ?? 'adult';
                        $pTypeLabel = match($pType) {
                        'child' => 'Trẻ em',
                        'infant' => 'Em bé',
                        default => 'Người lớn',
                        };
                        $pTypeBadgeClass = match($pType) {
                        'child' => 'bf-passenger-badge--child',
                        'infant' => 'bf-passenger-badge--infant',
                        default => 'bf-passenger-badge--adult',
                        };
                        $seatNumber = $ticket->flightSeat?->seat?->seat_number;
                        @endphp
                        <div class="bf-ticket-row">
                            <div class="bf-ticket-row-header">
                                <span class="bf-ticket-row-name">{{ $ticket->passenger->full_name }}</span>
                                <span class="bf-passenger-badge {{ $pTypeBadgeClass }}" style="font-size: 0.68rem; padding: 1px 6px;">
                                    {{ $pTypeLabel }}
                                </span>
                            </div>
                            <div class="bf-ticket-row-meta">
                                <span>
                                    <i class="bi bi-person-badge me-1"></i>
                                    @if ($seatNumber)
                                    Ghế: <strong>{{ $seatNumber }}</strong>
                                    @elseif ($pType === 'infant')
                                    Ngồi cùng người lớn
                                    @else
                                    Ghế hệ thống
                                    @endif
                                </span>
                                <span class="bf-ticket-row-price">{{ number_format($ticket->price, 0, ',', '.') }} đ</span>
                            </div>
                        </div>
                        @endforeach
                        @endforeach
                    </div>

                    {{-- TỔNG TIỀN THANH TOÁN --}}
                    <div class="bf-sidebar-total-box">
                        <div class="bf-sidebar-total-label">Tổng thanh toán:</div>
                        <div class="bf-sidebar-total-amount">{{ number_format($booking->total_amount, 0, ',', '.') }} đ</div>
                    </div>

                    <div class="bf-sidebar-security-badge">
                        <i class="bi bi-patch-check-fill text-success" aria-hidden="true"></i>
                        <span>Giá vé đã bao gồm thuế, phí sân bay và bảo hiểm chuyến bay theo quy định.</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

{{-- MODAL THÔNG BÁO HẾT HẠN (TRAVELOKA STYLE) --}}
<div class="modal fade" id="paymentExpiredModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="paymentExpiredModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bf-expired-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy" id="paymentExpiredModalLabel">Phiên thanh toán của bạn đã kết thúc</h5>
                <button type="button" class="btn-close" id="btn-close-expired-modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body py-3 text-muted">
                <p class="mb-2">Rất tiếc, bạn không thể tiếp tục đặt vé này. Ghế đã được tự động giải phóng do hết thời gian thanh toán (20 phút). Bạn có thể quay lại trang chủ để đặt lại.</p>
                <div class="text-danger small fst-italic">
                    Tự động chuyển về trang chủ sau <span id="redirect-seconds">5</span> giây...
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <a href="{{ route('home') }}" class="btn btn-primary w-100 py-2 fw-bold bf-btn-modal-home" id="btn-modal-redirect-home">
                    Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/payment.js'])
@endsection