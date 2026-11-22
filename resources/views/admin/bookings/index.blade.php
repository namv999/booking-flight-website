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

    <!-- Form tìm kiếm, bộ lọc & sắp xếp -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.bookings.index') }}">
                <div class="row g-2 mb-2">
                    <div class="col-12 col-lg-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm theo mã đơn (#BK-00012), họ tên, email hoặc mã vé (TK-...)..." value="{{ $search ?? '' }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <select name="sort" class="form-select" aria-label="Sắp xếp đơn hàng">
                            <option value="latest" @selected(($sort ?? '') === 'latest')>Sắp xếp: Mới nhất trước</option>
                            <option value="oldest" @selected(($sort ?? '') === 'oldest')>Sắp xếp: Cũ nhất trước</option>
                            <option value="amount_desc" @selected(($sort ?? '') === 'amount_desc')>Tổng tiền: Cao đến thấp</option>
                            <option value="amount_asc" @selected(($sort ?? '') === 'amount_asc')>Tổng tiền: Thấp đến cao</option>
                            <option value="id_desc" @selected(($sort ?? '') === 'id_desc')>Mã đơn: Giảm dần</option>
                            <option value="id_asc" @selected(($sort ?? '') === 'id_asc')>Mã đơn: Tăng dần</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        @if(!empty($search) || !empty($status) || !empty($passportStatus) || !empty($fromDate) || !empty($toDate) || (($sort ?? 'latest') !== 'latest'))
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="row g-2 pt-2 border-top">
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Trạng thái đơn</label>
                        <select name="status" class="form-select form-select-sm" aria-label="Lọc theo trạng thái">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending" @selected(($status ?? null) === 'pending')>Chờ thanh toán</option>
                            <option value="paid" @selected(($status ?? null) === 'paid')>Đã thanh toán</option>
                            <option value="cancelled" @selected(($status ?? null) === 'cancelled')>Đã hủy / Hết hạn</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Yêu cầu đổi hồ sơ / hộ chiếu</label>
                        <select name="passport_status" class="form-select form-select-sm" aria-label="Lọc theo yêu cầu cập nhật hồ sơ">
                            <option value="">Tất cả yêu cầu</option>
                            <option value="pending" @selected(($passportStatus ?? null) === 'pending')>Có yêu cầu đang chờ duyệt</option>
                            <option value="any" @selected(($passportStatus ?? null) === 'any')>Đơn có yêu cầu đổi hồ sơ</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Từ ngày đặt</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}">
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Đến ngày đặt</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(($passportStatus ?? null) === 'pending')
        <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill fs-4 text-warning"></i>
                <div>
                    <strong class="d-block text-dark">Đang hiển thị danh sách đơn có yêu cầu chỉnh sửa hồ sơ / hộ chiếu chờ duyệt</strong>
                    <span class="small text-secondary">Nhấp vào "Xử lý yêu cầu" tại đơn tương ứng để xem chi tiết và duyệt hoặc từ chối.</span>
                </div>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-dark btn-sm text-nowrap">
                <i class="bi bi-x-lg me-1"></i> Xóa lọc
            </a>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 130px;">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th class="text-center" style="width: 100px;">Số chặng</th>
                            <th class="text-end" style="width: 150px;">Tổng tiền</th>
                            <th class="text-center" style="width: 160px;">Trạng thái</th>
                            <th class="text-center" style="width: 130px;">Hồ sơ hành khách</th>
                            <th style="width: 150px;">Ngày đặt</th>
                            <th class="text-end pe-3" style="width: 130px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        @php
                            $hasPendingUpdate = ($booking->pending_passport_requests_count ?? 0) > 0;
                        @endphp
                        <tr class="{{ $hasPendingUpdate ? 'table-warning' : '' }}" style="{{ $hasPendingUpdate ? '--bs-table-bg: #fff9e6; --bs-table-accent-bg: #fff9e6;' : '' }}">
                            <td class="ps-3">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-primary fw-bold text-decoration-none font-monospace">
                                    #BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                                @if($hasPendingUpdate)
                                    <div class="mt-1">
                                        <span class="badge bg-warning text-dark border border-warning d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 0.7rem;">
                                            <i class="bi bi-exclamation-circle-fill text-danger"></i> Cần sửa hồ sơ
                                        </span>
                                    </div>
                                @endif
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
                            <td class="text-center">
                                @if($hasPendingUpdate)
                                    <span class="badge bg-warning text-dark border border-warning d-inline-flex align-items-center gap-1 shadow-sm px-2 py-1">
                                        <i class="bi bi-clock-history text-danger"></i> Cần duyệt ({{ $booking->pending_passport_requests_count }})
                                    </span>
                                @elseif(($booking->total_passport_requests_count ?? 0) > 0)
                                    <span class="badge bg-light text-secondary border d-inline-flex align-items-center gap-1 px-2 py-1">
                                        <i class="bi bi-check2 text-success"></i> Đã xử lý
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-dark">{{ $booking->created_at?->format('H:i') }}</div>
                                <div class="small text-muted">{{ $booking->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td class="text-end pe-3 text-nowrap">
                                @if($hasPendingUpdate)
                                    <a href="{{ route('admin.bookings.show', $booking) }}#passport-update-requests" class="btn btn-sm btn-warning fw-semibold text-dark d-inline-flex align-items-center gap-1 shadow-sm" title="Xử lý yêu cầu chỉnh sửa hồ sơ hành khách">
                                        <i class="bi bi-pencil-square"></i> Xử lý ngay
                                    </a>
                                @else
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-eye"></i> Xem
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
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

