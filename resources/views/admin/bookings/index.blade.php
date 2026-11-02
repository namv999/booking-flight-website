@extends('layouts.admin')

@section('title', 'Quản lý đơn đặt vé - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="bf-eyebrow">Quản trị giao dịch</span>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Đơn đặt vé</h1>
            <p class="text-muted small mb-0">Theo dõi toàn bộ đơn hàng, đối soát trạng thái thanh toán và hỗ trợ hành khách.</p>
        </div>
    </div>

    <!-- Form tìm kiếm + lọc trạng thái -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.bookings.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm theo mã đơn (#BK-00012), họ tên, email hoặc mã vé (TK-...)..." value="{{ $search ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <select name="status" class="form-select" aria-label="Lọc theo trạng thái">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending" @selected(($status ?? null) === 'pending')>Chờ thanh toán</option>
                            <option value="paid" @selected(($status ?? null) === 'paid')>Đã thanh toán</option>
                            <option value="cancelled" @selected(($status ?? null) === 'cancelled')>Đã hủy / Hết hạn</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">Tìm kiếm</button>
                        @if(!empty($search) || !empty($status))
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 120px;">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th class="text-center" style="width: 100px;">Số chặng</th>
                            <th class="text-end" style="width: 150px;">Tổng tiền</th>
                            <th class="text-center" style="width: 160px;">Trạng thái</th>
                            <th style="width: 150px;">Ngày đặt</th>
                            <th class="text-end pe-3" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-primary fw-bold text-decoration-none font-monospace">
                                    #BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($booking->user?->name ?? 'K', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $booking->user?->name ?? 'Không xác định' }}</div>
                                        <div class="small text-muted">{{ $booking->user?->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    {{ $booking->booking_flights_count }} chặng
                                </span>
                            </td>
                            <td class="text-end fw-semibold font-monospace">
                                {{ number_format($booking->total_amount, 0, ',', '.') }} đ
                            </td>
                            <td class="text-center">
                                @if($booking->status === 'paid')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i> Đã thanh toán
                                    </span>
                                @elseif($booking->status === 'cancelled')
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <i class="bi bi-x-circle me-1"></i> Đã hủy / Hết hạn
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-hourglass-split me-1"></i> Chờ thanh toán
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-dark">{{ $booking->created_at?->format('H:i') }}</div>
                                <div class="small text-muted">{{ $booking->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-2 d-block mb-2 text-secondary"></i>
                                Không tìm thấy đơn đặt vé nào phù hợp.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Phân trang -->
            @if($bookings->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

