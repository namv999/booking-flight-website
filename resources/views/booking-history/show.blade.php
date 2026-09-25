{{-- resources/views/booking-history/show.blade.php --}}
{{-- CHI TIẾT ĐƠN ĐẶT VÉ & XÁC NHẬN VÉ ĐIỆN TỬ (E-TICKET) --}}

@extends('layouts.app')

@section('title', 'Chi tiết đơn đặt vé #' . $booking->id . ' - Jet Charter')

@section('styles')
    @vite(['resources/css/flights.css'])
@endsection

@section('content')
<div class="bf-results-page">
    {{-- 1. THANH TIẾN TRÌNH (HIỂN THỊ TRỌN VẸN KHI ĐƠN ĐÃ THANH TOÁN THÀNH CÔNG) --}}
    @if ($booking->status === 'paid')
        <div class="bf-step-wizard bf-print-hide">
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
                    <li class="bf-step-item completed">
                        <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                        <span class="d-none d-sm-inline">Thanh toán</span>
                    </li>
                    <li class="bf-step-divider completed"></li>
                    <li class="bf-step-item active" aria-current="step">
                        <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                        <span>Hoàn tất</span>
                    </li>
                </ol>
            </div>
        </div>
    @endif

    <div class="container pt-4">
        {{-- THÔNG BÁO FLASH MESSAGE --}}
        @if (session('status'))
            <div class="bf-success-hero bf-print-hide">
                <div class="bf-success-hero-icon" aria-hidden="true">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div class="bf-success-hero-content">
                    <h2>{{ session('status') }}</h2>
                    <p>Đơn đặt vé đã được xác nhận thành công. Vé điện tử và hành trình chi tiết của bạn đã sẵn sàng bên dưới.</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4 bf-print-hide" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        {{-- BREADCRUMB / TIÊU ĐỀ TRANG --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb" class="bf-print-hide">
                    <ol class="breadcrumb small mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('booking-history.index') }}" class="text-decoration-none">Lịch sử đặt vé</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Đơn #BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-3">
                    <h1 class="h4 fw-bold text-navy mb-0" style="color: var(--bf-navy);">
                        Đơn đặt vé #BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>
                    @if ($booking->status === 'paid')
                        <span class="bf-status-badge bf-status-badge--paid">
                            <i class="bi bi-check-circle-fill"></i> Đã thanh toán
                        </span>
                    @elseif ($booking->status === 'cancelled')
                        <span class="bf-status-badge bf-status-badge--cancelled">
                            <i class="bi bi-x-circle-fill"></i> Đã hủy / Hết hạn
                        </span>
                    @else
                        <span class="bf-status-badge bf-status-badge--pending">
                            <i class="bi bi-clock-fill"></i> Chờ thanh toán
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-0 mt-1">Đặt lúc {{ $booking->created_at->format('H:i, d/m/Y') }}</p>
            </div>

            <div class="d-flex gap-2 bf-print-hide">
                @if ($booking->status === 'paid')
                    <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> In vé / Lưu PDF
                    </button>
                @endif
                <a href="{{ route('booking-history.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                    &laquo; Danh sách vé
                </a>
            </div>
        </div>

        <div class="row g-4">
            {{-- CỘT TRÁI: THÔNG TIN HÀNH TRÌNH, VÉ ĐIỆN TỬ VÀ THANH TOÁN --}}
            <main class="col-lg-8 col-md-7">
                {{-- 1. THẺ HÀNH TRÌNH CHUYẾN BAY --}}
                @foreach ($booking->bookingFlights as $bf)
                    @php
                        $flight = $bf->flight;
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
                    @endphp

                    <div class="bf-payment-card mb-4">
                        <div class="bf-payment-card__header">
                            <h2 class="bf-payment-card__title">
                                <i class="bi bi-airplane-engines text-primary" aria-hidden="true"></i>
                                <span>Thông tin hành trình</span>
                            </h2>
                            <span class="badge bg-light text-navy border">
                                Chuyến bay: #FL-{{ str_pad($flight->id ?? 0, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>

                        <div class="row align-items-center g-3 mb-4">
                            <div class="col-12 col-sm-4 text-center text-sm-start">
                                <div class="h2 fw-bold text-navy mb-0">{{ $departureAirport?->iata_code ?? $flight->departure_airport_id }}</div>
                                <div class="fw-bold text-navy">{{ $departureAirport?->city ?? 'Sân bay đi' }}</div>
                                <div class="text-muted small">{{ $departureAirport?->name }}</div>
                                <div class="mt-2 text-primary fw-bold fs-5">{{ $depTime?->format('H:i') }}</div>
                                <div class="text-muted small">{{ $depTime?->format('d/m/Y') }}</div>
                            </div>

                            <div class="col-12 col-sm-4 text-center">
                                <div class="text-muted small mb-1">{{ $durationText }} &bull; Bay thẳng</div>
                                <div class="bf-flight-track position-relative my-2">
                                    <hr class="my-0">
                                    <i class="bi bi-airplane position-absolute top-50 start-50 translate-middle text-primary bg-white px-2 fs-5"></i>
                                </div>
                                <div class="small fw-bold text-muted">{{ $airlineName }} ({{ $aircraftModel }})</div>
                            </div>

                            <div class="col-12 col-sm-4 text-center text-sm-end">
                                <div class="h2 fw-bold text-navy mb-0">{{ $arrivalAirport?->iata_code ?? $flight->arrival_airport_id }}</div>
                                <div class="fw-bold text-navy">{{ $arrivalAirport?->city ?? 'Sân bay đến' }}</div>
                                <div class="text-muted small">{{ $arrivalAirport?->name }}</div>
                                <div class="mt-2 text-primary fw-bold fs-5">{{ $arrTime?->format('H:i') }}</div>
                                <div class="text-muted small">{{ $arrTime?->format('d/m/Y') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. DANH SÁCH VÉ ĐIỆN TỬ (E-TICKETS / BOARDING PASSES) --}}
                    <div class="bf-payment-card mb-4">
                        <div class="bf-payment-card__header">
                            <h2 class="bf-payment-card__title">
                                <i class="bi bi-ticket-perforated-fill text-primary" aria-hidden="true"></i>
                                <span>Vé điện tử hành khách ({{ $bf->tickets->count() }} vé)</span>
                            </h2>
                            <span class="text-muted small">Xuất trình mã vé khi làm thủ tục check-in</span>
                        </div>

                        <div class="bf-etickets-container">
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

                                <div class="bf-eticket-card">
                                    <div class="bf-eticket-header">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-person-circle text-primary fs-5"></i>
                                            <strong class="text-navy fs-6">{{ $ticket->passenger->full_name }}</strong>
                                            <span class="bf-passenger-badge {{ $pTypeBadgeClass }}" style="font-size: 0.68rem; padding: 1px 6px;">
                                                {{ $pTypeLabel }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-muted small me-1">Mã vé:</span>
                                            <span class="bf-eticket-code">#{{ $ticket->ticket_code }}</span>
                                        </div>
                                    </div>

                                    <div class="bf-eticket-grid">
                                        <div class="bf-eticket-field">
                                            <label>Chỗ ngồi (Seat)</label>
                                            @if ($seatNumber)
                                                <span class="text-primary fw-bold fs-6">Ghế {{ $seatNumber }}</span>
                                            @elseif ($pType === 'infant')
                                                <span class="text-muted small">Ngồi cùng người lớn</span>
                                            @elseif ($booking->status === 'cancelled')
                                                <span class="badge bg-secondary text-white">Đã giải phóng ghế</span>
                                            @else
                                                <span class="text-muted small">Ghế tự động</span>
                                            @endif
                                        </div>

                                        <div class="bf-eticket-field">
                                            <label>Giấy tờ tùy thân</label>
                                            <span>{{ $ticket->passenger->document_number ?: 'Chưa cung cấp' }}</span>
                                        </div>

                                        <div class="bf-eticket-field">
                                            <label>Hành lý</label>
                                            <span>{{ $ticket->baggageAddon->name ?? 'Tiêu chuẩn (7kg xách tay)' }}</span>
                                        </div>

                                        <div class="bf-eticket-field">
                                            <label>Giá vé</label>
                                            <span class="text-navy">{{ number_format($ticket->price, 0, ',', '.') }} đ</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- 3. THÔNG TIN THANH TOÁN (NẾU CÓ) --}}
                @if ($booking->payment)
                    <div class="bf-payment-card mb-4">
                        <div class="bf-payment-card__header">
                            <h2 class="bf-payment-card__title">
                                <i class="bi bi-credit-card-fill text-primary" aria-hidden="true"></i>
                                <span>Chi tiết giao dịch thanh toán</span>
                            </h2>
                            <span class="badge bg-success text-white">
                                <i class="bi bi-check-all me-1"></i>Thành công
                            </span>
                        </div>

                        <div class="row g-3 small">
                            <div class="col-12 col-sm-6">
                                <div class="text-muted mb-1">Mã giao dịch:</div>
                                <div class="fw-bold font-monospace fs-6">{{ $booking->payment->transaction_code }}</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted mb-1">Phương thức thanh toán:</div>
                                <div class="fw-bold">
                                    {{ $booking->payment->method === 'the_tin_dung' ? 'Thẻ tín dụng / Ghi nợ quốc tế' : 'Ví điện tử' }}
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted mb-1">Thời gian thanh toán:</div>
                                <div class="fw-bold">{{ $booking->payment->paid_at ? \Carbon\Carbon::parse($booking->payment->paid_at)->format('H:i:s - d/m/Y') : '-' }}</div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted mb-1">Tổng tiền thanh toán:</div>
                                <div class="fw-bold text-primary fs-6">{{ number_format($booking->payment->amount, 0, ',', '.') }} VND</div>
                            </div>
                        </div>
                    </div>
                @endif
            </main>

            {{-- CỘT PHẢI: SIDEBAR TỔNG KẾT & HÀNH ĐỘNG --}}
            <aside class="col-lg-4 col-md-5">
                <div class="bf-booking-sidebar">
                    <h3 class="bf-booking-sidebar__title">
                        <i class="bi bi-receipt text-primary" aria-hidden="true"></i>
                        <span>Tóm tắt thanh toán</span>
                    </h3>

                    <div class="bf-sidebar-meta-row">
                        <span>Mã đơn hàng:</span>
                        <strong class="font-monospace">#BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</strong>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Ngày tạo:</span>
                        <span>{{ $booking->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Trạng thái:</span>
                        @if ($booking->status === 'paid')
                            <strong class="text-success"><i class="bi bi-check-circle me-1"></i>Đã thanh toán</strong>
                        @elseif ($booking->status === 'cancelled')
                            <strong class="text-danger"><i class="bi bi-x-circle me-1"></i>Đã hủy</strong>
                        @else
                            <strong class="text-warning"><i class="bi bi-clock me-1"></i>Chờ thanh toán</strong>
                        @endif
                    </div>

                    <div class="bf-sidebar-total-box mt-3 pt-3">
                        <div class="bf-sidebar-total-label">Tổng chi phí:</div>
                        <div class="bf-sidebar-total-amount">{{ number_format($booking->total_amount, 0, ',', '.') }} đ</div>
                    </div>

                    {{-- NÚT HÀNH ĐỘNG CHÍNH --}}
                    <div class="mt-4 d-flex flex-column gap-2 bf-print-hide">
                        @if ($booking->status === 'paid')
                            <button type="button" class="btn btn-primary w-100 py-2 fw-bold" onclick="window.print()">
                                <i class="bi bi-printer me-2"></i>In vé điện tử / Lưu PDF
                            </button>
                            <a href="{{ route('home') }}" class="btn btn-outline-primary w-100 py-2 fw-bold">
                                <i class="bi bi-plus-circle me-2"></i>Đặt thêm chuyến bay khác
                            </a>
                        @elseif ($booking->status === 'pending')
                            <a href="{{ route('payment.show', $booking) }}" class="btn btn-warning w-100 py-2 fw-bold text-dark">
                                <i class="bi bi-credit-card me-2"></i>Tiếp tục thanh toán ngay
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary w-100 py-2">
                                Về trang chủ
                            </a>
                        @else
                            <a href="{{ route('home') }}" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="bi bi-search me-2"></i>Tìm & Đặt lại chuyến bay
                            </a>
                        @endif

                        <a href="{{ route('booking-history.index') }}" class="btn btn-outline-secondary w-100 py-2">
                            <i class="bi bi-arrow-left me-1"></i>Về danh sách vé của tôi
                        </a>
                    </div>

                    <div class="bf-sidebar-security-badge mt-4">
                        <i class="bi bi-shield-check text-success" aria-hidden="true"></i>
                        <span>Cần trợ giúp? Hotline hỗ trợ hành khách 24/7: <strong>1900 1234</strong></span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection