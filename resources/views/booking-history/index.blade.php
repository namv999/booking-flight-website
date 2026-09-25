{{-- resources/views/booking-history/index.blade.php --}}
{{-- DANH SÁCH LỊCH SỬ ĐẶT VÉ CỦA USER --}}

@extends('layouts.app')

@section('title', 'Lịch sử đặt vé - Jet Charter')

@section('styles')
@vite(['resources/css/flights.css'])
@endsection

@section('content')
<div class="bf-results-page">
    <div class="container py-4">
        {{-- BREADCRUMB & TIÊU ĐỀ --}}
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Lịch sử đặt vé</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-navy mb-1" style="color: var(--bf-navy);">Chuyến đi & Lịch sử đặt vé</h1>
                <p class="text-muted small mb-0">Quản lý toàn bộ vé máy bay điện tử và lịch sử giao dịch của bạn tại Jet Charter.</p>
            </div>
            <a href="{{ route('home') }}" class="btn btn-primary btn-sm px-3 py-2 fw-bold text-white">
                <i class="bi bi-search me-1"></i> Tìm chuyến bay mới
            </a>
        </div>

        @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0 fs-5"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
        @endif

        @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
        @endif

        @if ($bookings->isEmpty())
        {{-- TRẠNG THÁI CHƯA CÓ BOOKING NÀO (EMPTY STATE) --}}
        <div class="bf-empty-card py-5 my-4">
            <div class="bf-empty-icon-box">
                <i class="bi bi-ticket-perforated" aria-hidden="true"></i>
            </div>
            <h3 class="bf-empty-title">Bạn chưa có đơn đặt vé nào</h3>
            <p class="bf-empty-desc">
                Hãy bắt đầu chuyến hành trình tuyệt vời tiếp theo cùng Jet Charter ngay hôm nay!
            </p>
            <a href="{{ route('home') }}" class="btn btn-primary fw-bold px-4 py-2 mt-2">
                <i class="bi bi-airplane me-1"></i> Khám phá chuyến bay ngay
            </a>
        </div>
        @else
        <div class="bf-payment-card p-0 overflow-hidden shadow-sm border">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Mã đơn</th>
                            <th class="py-3 text-muted small text-uppercase">Hành trình bay</th>
                            <th class="py-3 text-muted small text-uppercase">Ngày bay</th>
                            <th class="py-3 text-muted small text-uppercase">Ngày đặt</th>
                            <th class="py-3 text-muted small text-uppercase">Trạng thái</th>
                            <th class="py-3 text-muted small text-uppercase text-end">Tổng tiền</th>
                            <th class="pe-4 py-3 text-center text-muted small text-uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                        @php
                        $firstBf = $booking->bookingFlights->first();
                        $flight = $firstBf?->flight;
                        $depAirport = $flight?->departureAirport;
                        $arrAirport = $flight?->arrivalAirport;
                        $depTime = $flight ? \Carbon\Carbon::parse($flight->departure_time) : null;
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <strong class="font-monospace text-primary">#BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</strong>
                            </td>
                            <td>
                                @if ($flight)
                                <div class="fw-bold text-navy">
                                    {{ $depAirport?->iata_code ?? $flight->departure_airport_id }}
                                    <i class="bi bi-arrow-right text-muted mx-1"></i>
                                    {{ $arrAirport?->iata_code ?? $flight->arrival_airport_id }}
                                </div>
                                <div class="text-muted small" style="font-size: 0.76rem;">
                                    {{ $depAirport?->city }} đến {{ $arrAirport?->city }}
                                </div>
                                @else
                                <span class="text-muted fst-italic">Không có dữ liệu</span>
                                @endif
                            </td>
                            <td>
                                @if ($depTime)
                                <div class="fw-bold text-navy">{{ $depTime->format('H:i') }}</div>
                                <div class="text-muted small" style="font-size: 0.76rem;">{{ $depTime->format('d/m/Y') }}</div>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-navy">{{ $booking->created_at->format('d/m/Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.76rem;">{{ $booking->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                @if ($booking->status === 'paid')
                                <span class="bf-status-badge bf-status-badge--paid">
                                    <i class="bi bi-check-circle-fill"></i> Đã thanh toán
                                </span>
                                @elseif ($booking->status === 'cancelled')
                                <span class="bf-status-badge bf-status-badge--cancelled">
                                    <i class="bi bi-x-circle-fill"></i> Đã hủy
                                </span>
                                @else
                                <span class="bf-status-badge bf-status-badge--pending">
                                    <i class="bi bi-clock-fill"></i> Chờ thanh toán
                                </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong class="text-navy">{{ number_format($booking->total_amount, 0, ',', '.') }} đ</strong>
                            </td>
                            <td class="pe-4 text-center">
                                <a href="{{ route('booking-history.show', $booking->id) }}" class="btn btn-outline-primary btn-sm px-3">
                                    Chi tiết <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($bookings->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $bookings->links() }}
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection