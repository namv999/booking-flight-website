@extends('layouts.admin')

@section('title', 'Quản lý đơn đặt vé - Jet Charter Flights')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý Đơn đặt vé</h2>
    </div>

    <!-- Form tìm kiếm + lọc trạng thái -->
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-7">
                <input type="text" name="search" class="form-control" placeholder="Tìm theo mã đơn, tên, email hoặc mã vé..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" aria-label="Lọc theo trạng thái">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" @selected(($status ?? null) === 'pending')>Chờ thanh toán</option>
                    <option value="paid" @selected(($status ?? null) === 'paid')>Đã thanh toán</option>
                    <option value="cancelled" @selected(($status ?? null) === 'cancelled')>Đã hủy / Hết hạn</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-secondary flex-grow-1" type="submit">Tìm kiếm</button>
                @if(!empty($search) || !empty($status))
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-danger">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Người đặt</th>
                            <th class="text-center">Số chặng</th>
                            <th class="text-end">Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-primary fw-semibold text-decoration-none font-monospace">
                                    #BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>
                                {{ $booking->user?->name ?? 'Không xác định' }}
                                <div class="small text-muted">{{ $booking->user?->email }}</div>
                            </td>
                            <td class="text-center">{{ $booking->booking_flights_count }}</td>
                            <td class="text-end">{{ number_format($booking->total_amount, 0, ',', '.') }} đ</td>
                            <td>
                                @if($booking->status === 'paid')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                @elseif($booking->status === 'cancelled')
                                    <span class="badge bg-secondary">Đã hủy / Hết hạn</span>
                                @else
                                    <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                                @endif
                            </td>
                            <td>{{ $booking->created_at?->format('H:i d/m/Y') }}</td>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-info text-white">Xem</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Không tìm thấy đơn đặt vé.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Phân trang -->
            <div class="d-flex justify-content-center">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
