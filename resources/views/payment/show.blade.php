@extends('layouts.app')

@section('title', 'Thanh toán đơn đặt vé #' . $booking->id . ' - Jet Charter')

@section('styles')
@vite(['resources/css/flights.css'])
<style>
/* Passport Risk Popup Modal */
.bf-passport-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1060;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 1;
    visibility: visible;
    transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
}
.bf-passport-modal-overlay.closing {
    opacity: 0;
    visibility: hidden;
}
.bf-passport-modal-container {
    width: 100%;
    max-width: 600px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalEaseIn 0.35s ease-in-out forwards;
    transform-origin: center center;
}
.bf-passport-modal-overlay.closing .bf-passport-modal-container {
    animation: modalEaseOut 0.25s ease-in-out forwards;
}
@keyframes modalEaseIn {
    0% { opacity: 0; transform: scale(0.92) translateY(16px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes modalEaseOut {
    0% { opacity: 1; transform: scale(1) translateY(0); }
    100% { opacity: 0; transform: scale(0.92) translateY(16px); }
}
.bf-passport-modal--yellow { border-top: 5px solid #f59e0b; }
.bf-passport-modal--yellow .bf-passport-modal-icon-badge {
    background: #fef3c7; color: #d97706; border: 1px solid #fde68a;
}
.bf-passport-modal--yellow .bf-passport-modal-title { color: #92400e; }
.bf-passport-modal--yellow .bf-passport-modal-btn-confirm:not(:disabled) {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
}
.bf-passport-modal--yellow .bf-passport-modal-btn-confirm:not(:disabled):hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(217, 119, 6, 0.45);
}
.bf-passport-modal--red { border-top: 5px solid #ef4444; }
.bf-passport-modal--red .bf-passport-modal-icon-badge {
    background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;
}
.bf-passport-modal--red .bf-passport-modal-title { color: #991b1b; }
.bf-passport-modal--red .bf-passport-modal-btn-confirm:not(:disabled) {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
}
.bf-passport-modal--red .bf-passport-modal-btn-confirm:not(:disabled):hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(220, 38, 38, 0.45);
}
.bf-passport-modal-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 24px 24px 16px;
    background: #fafbfc;
    border-bottom: 1px solid #edf2f7;
}
.bf-passport-modal-icon-badge {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.bf-passport-modal-title {
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0 0 4px;
    line-height: 1.35;
}
.bf-passport-modal-subtitle {
    font-size: 0.84rem;
    color: #64748b;
    margin: 0;
    line-height: 1.45;
}
.bf-passport-modal-body {
    padding: 20px 24px;
    max-height: 55vh;
    overflow-y: auto;
}
.bf-passport-modal-checkbox-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    margin-top: 16px;
    transition: all 0.2s ease;
}
.bf-passport-modal-checkbox-card:hover {
    border-color: #cbd5e1;
    background: #ffffff;
}
.bf-passport-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: #fafbfc;
    border-top: 1px solid #edf2f7;
}
.bf-passport-modal-btn-confirm {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 24px;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 700;
    border: none;
    transition: all 0.25s ease-in-out;
    cursor: pointer;
}
.bf-passport-modal-btn-confirm:disabled {
    background: #cbd5e1 !important;
    color: #64748b !important;
    cursor: not-allowed !important;
    border: 1px solid #cbd5e1 !important;
    box-shadow: none !important;
    transform: none !important;
    opacity: 0.7 !important;
}
</style>
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

                {{-- THÔNG BÁO HỖ TRỢ CẬP NHẬT THÔNG TIN HỘ CHIẾU (GIỮ TRÊN SITE) --}}
                @if (isset($passportRisk['level']) && $passportRisk['level'] !== null)
                    <div class="alert alert-light border shadow-sm mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted small">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle-fill text-primary flex-shrink-0"></i>
                                <span>Thông tin hộ chiếu có thể nhập sai hoặc vừa được gia hạn? Vui lòng liên hệ hotline 1900 1234 để được hỗ trợ cập nhật.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fw-semibold" id="btn-reopen-passport-modal" style="font-size: 0.78rem;">
                                <i class="bi bi-shield-exclamation me-1"></i>Xem lại cảnh báo
                            </button>
                        </div>
                    </div>
                @endif

                {{-- FORM THANH TOÁN & GIẢ LẬP KẾT QUẢ --}}
                <form method="POST" action="{{ route('payment.store', $booking) }}" id="bf-payment-form">
                    @csrf

                    @if (isset($passportRisk['level']) && $passportRisk['level'] !== null)
                        <input class="form-check-input d-none" type="checkbox" name="confirm_passport_risk" value="1" id="confirm_passport_risk" {{ old('confirm_passport_risk') ? 'checked' : '' }} required>
                    @endif

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
                    $legs = $booking->ordered_legs;
                    $legCount = $legs->count();
                    $firstFlight = $legs->first()?->flight;
                    $lastFlight = $legs->last()?->flight;
                    $isRoundTrip = $legCount === 2 && $firstFlight && $lastFlight
                        && $firstFlight->departure_airport_id === $lastFlight->arrival_airport_id
                        && $firstFlight->arrival_airport_id === $lastFlight->departure_airport_id;
                    $totalTicketsCount = $legs->sum(fn ($bf) => $bf->tickets->count());
                @endphp

                <div class="bf-booking-sidebar">
                    <h3 class="bf-booking-sidebar__title">
                        <i class="bi bi-receipt text-primary" aria-hidden="true"></i>
                        <span>Tóm tắt đơn hàng</span>
                    </h3>

                    @foreach ($legs as $i => $bf)
                        @php
                            $f = $bf->flight;
                            $depTime = $f ? \Carbon\Carbon::parse($f->departure_time) : null;
                            $arrTime = $f ? \Carbon\Carbon::parse($f->arrival_time) : null;
                            $diffMinutes = ($depTime && $arrTime) ? $depTime->diffInMinutes($arrTime) : 0;
                            $hours = intdiv($diffMinutes, 60);
                            $mins = $diffMinutes % 60;
                            $durationText = ($hours > 0 ? "{$hours}h " : "") . ($mins > 0 ? "{$mins}m" : "00m");
                            $airlineName = $f?->aircraft?->airline?->name ?? 'Hãng hàng không';
                            $aircraftModel = $f?->aircraft?->model ?? 'Aircraft';
                            $departureAirport = $f?->departureAirport;
                            $arrivalAirport = $f?->arrivalAirport;
                            $legLabel = $legCount > 1
                                ? ($isRoundTrip ? ($i === 0 ? 'Chuyến đi' : 'Chuyến về') : 'Chặng '.($i + 1))
                                : null;
                        @endphp
                        @if ($f)
                            <div class="{{ ! $loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                                @if ($legLabel)
                                    <div class="mb-2"><span class="badge bg-primary">{{ $legLabel }}</span></div>
                                @endif

                                <div class="bf-sidebar-route">
                                    <div class="bf-sidebar-route-item">
                                        <strong>{{ $departureAirport?->iata_code ?? $f->departure_airport_id }}</strong>
                                        <small>{{ $departureAirport?->city ?? 'Sân bay đi' }}</small>
                                    </div>
                                    <i class="bi bi-arrow-right bf-sidebar-arrow" aria-hidden="true"></i>
                                    <div class="bf-sidebar-route-item text-end">
                                        <strong>{{ $arrivalAirport?->iata_code ?? $f->arrival_airport_id }}</strong>
                                        <small>{{ $arrivalAirport?->city ?? 'Sân bay đến' }}</small>
                                    </div>
                                </div>

                                <div class="bf-sidebar-airline">
                                    <i class="bi bi-airplane" aria-hidden="true"></i>
                                    <div>
                                        <div class="fw-bold text-navy">{{ $airlineName }}</div>
                                        <div class="small text-muted">{{ $aircraftModel }} &bull; Chuyến #FL-{{ str_pad($f->id, 4, '0', STR_PAD_LEFT) }}</div>
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
                            </div>
                        @endif
                    @endforeach

                    {{-- DANH SÁCH CHI TIẾT VÉ HÀNH KHÁCH --}}
                    <div class="bf-ticket-breakdown-list">
                        <div class="bf-ticket-breakdown-title">
                            <span>Chi tiết vé hành khách</span>
                            <span class="text-muted small">({{ $totalTicketsCount }} vé)</span>
                        </div>

                        @foreach ($legs as $i => $bf)
                            @if ($legCount > 1)
                                <div class="small fw-bold text-navy mt-2 mb-1">
                                    {{ $isRoundTrip ? ($i === 0 ? 'Chuyến đi' : 'Chuyến về') : 'Chặng '.($i + 1) }}
                                    · {{ $bf->flight?->departureAirport?->iata_code }} → {{ $bf->flight?->arrivalAirport?->iata_code }}
                                </div>
                            @endif
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
                            @if ($legCount > 1)
                                <div class="d-flex justify-content-between small text-muted mb-2">
                                    <span>Tạm tính chặng này</span>
                                    <span>{{ number_format($bf->tickets->sum('price'), 0, ',', '.') }} đ</span>
                                </div>
                            @endif
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

{{-- POPUP BOX CẢNH BÁO RỦI RO HỘ CHIẾU --}}
@if (isset($passportRisk['level']) && $passportRisk['level'] !== null)
    @php
        $refDateStr = !empty($passportRisk['ref_date']) ? \Carbon\Carbon::parse($passportRisk['ref_date'])->format('d/m/Y') : '';
        $isRedRisk = $passportRisk['level'] === 'red';
    @endphp

    <div class="bf-passport-modal-overlay" id="bfPassportRiskOverlay">
        <div class="bf-passport-modal-container {{ $isRedRisk ? 'bf-passport-modal--red' : 'bf-passport-modal--yellow' }}" id="bfPassportRiskModal" role="dialog" aria-modal="true" aria-labelledby="passportRiskModalTitle">
            <div class="bf-passport-modal-header">
                <div class="bf-passport-modal-icon-badge">
                    @if ($isRedRisk)
                        <i class="bi bi-x-octagon-fill"></i>
                    @else
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    @endif
                </div>
                <div>
                    <h3 class="bf-passport-modal-title" id="passportRiskModalTitle">
                        @if ($isRedRisk)
                            Cảnh báo: Hộ chiếu đã hết hạn
                        @else
                            Nhắc nhở: Kiểm tra thời hạn hộ chiếu
                        @endif
                    </h3>
                    <p class="bf-passport-modal-subtitle">
                        Vui lòng kiểm tra kỹ thông tin giấy tờ để tránh nguy cơ bị từ chối bay
                    </p>
                </div>
            </div>

            <div class="bf-passport-modal-body">
                @foreach ($passportRisk['items'] as $riskItem)
                    @php
                        $itemPassenger = $riskItem['passenger'];
                        $itemExpiryStr = !empty($riskItem['expiry_date']) ? \Carbon\Carbon::parse($riskItem['expiry_date'])->format('d/m/Y') : '';
                    @endphp

                    @if ($riskItem['level'] === 'red')
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert">
                            <i class="bi bi-x-octagon-fill fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                Hộ chiếu của <strong>{{ $itemPassenger->full_name }}</strong> hết hạn ngày <strong>{{ $itemExpiryStr }}</strong>, TRƯỚC khi hành trình quốc tế kết thúc ({{ $refDateStr }}). Hành khách có nguy cơ cao bị từ chối khởi hành hoặc nhập cảnh.
                            </div>
                        </div>
                    @elseif ($riskItem['level'] === 'yellow')
                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                Hộ chiếu của <strong>{{ $itemPassenger->full_name }}</strong> còn hiệu lực đến <strong>{{ $itemExpiryStr }}</strong> nhưng chưa đủ 6 tháng kể từ ngày hạ cánh chặng quốc tế cuối ({{ $refDateStr }}). Một số quốc gia và hãng hàng không có thể từ chối nhập cảnh hoặc từ chối cho lên máy bay.
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="bf-passport-modal-checkbox-card">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="modal_confirm_passport_risk" {{ old('confirm_passport_risk') ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold {{ $isRedRisk ? 'text-danger' : 'text-warning-emphasis' }}" for="modal_confirm_passport_risk" style="cursor: pointer;">
                            Tôi đã đọc và hiểu rủi ro về hộ chiếu nêu trên và vẫn muốn tiếp tục thanh toán.
                        </label>
                    </div>
                </div>
            </div>

            <div class="bf-passport-modal-footer">
                <button type="button" class="bf-passport-modal-btn-confirm" id="btn-passport-acknowledge" disabled>
                    <span>Tôi đã hiểu</span>
                    <i class="bi bi-check2 ms-1"></i>
                </button>
            </div>
        </div>
    </div>
@endif

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
    @if (isset($passportRisk['level']) && $passportRisk['level'] !== null)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passportOverlay = document.getElementById('bfPassportRiskOverlay');
            const modalCheckbox = document.getElementById('modal_confirm_passport_risk');
            const btnAcknowledge = document.getElementById('btn-passport-acknowledge');
            const hiddenCheckbox = document.getElementById('confirm_passport_risk');
            const btnReopen = document.getElementById('btn-reopen-passport-modal');

            if (passportOverlay && modalCheckbox && btnAcknowledge) {
                document.body.style.overflow = 'hidden';

                modalCheckbox.addEventListener('change', function () {
                    btnAcknowledge.disabled = !this.checked;
                });

                btnAcknowledge.addEventListener('click', function () {
                    if (this.disabled) return;

                    if (hiddenCheckbox) {
                        hiddenCheckbox.checked = true;
                    }

                    passportOverlay.classList.add('closing');
                    setTimeout(function () {
                        passportOverlay.style.display = 'none';
                        document.body.style.overflow = '';
                    }, 250);
                });

                if (btnReopen) {
                    btnReopen.addEventListener('click', function (e) {
                        e.preventDefault();
                        passportOverlay.classList.remove('closing');
                        passportOverlay.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    });
                }

                const paymentForm = document.getElementById('bf-payment-form');
                if (paymentForm && hiddenCheckbox) {
                    paymentForm.addEventListener('submit', function (e) {
                        if (!hiddenCheckbox.checked) {
                            e.preventDefault();
                            passportOverlay.classList.remove('closing');
                            passportOverlay.style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                    });
                }
            }
        });
    </script>
    @endif
@endsection