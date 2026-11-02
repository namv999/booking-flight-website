{{-- resources/views/admin/dashboard.blade.php --}}
{{-- Dashboard ADMIN (route 'admin.dashboard') - Bảng điều khiển quản trị vận hành Jet Charter --}}

@extends('layouts.admin')

@section('title', 'Bảng điều khiển quản trị - Jet Charter Flights')

@section('content')
<section class="bf-admin-heading mb-4">
    <div>
        <span class="bf-eyebrow">Hệ thống điều hành bay Jet Charter</span>
        <h1>Bảng điều khiển Tổng quan</h1>
        <p class="text-muted">Dữ liệu tài chính, hiệu suất chuyến bay và trạng thái vận hành theo thời gian thực.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark border px-3 py-2">
            <i class="bi bi-clock me-1 text-primary"></i> Hôm nay: {{ \Carbon\Carbon::now()->format('d/m/Y') }}
        </span>
    </div>
</section>

{{-- CẢNH BÁO HỘ CHIẾU CHỜ DUYỆT (NẾU CÓ) --}}
@if($pendingPassportCount > 0)
<div class="alert alert-warning border-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-3">
        <div class="fs-3 text-warning">
            <i class="bi bi-exclamation-octagon-fill"></i>
        </div>
        <div>
            <strong class="d-block text-dark">Có {{ $pendingPassportCount }} yêu cầu sửa đổi hộ chiếu đang chờ phê duyệt</strong>
            <small class="text-secondary">Hành khách đã gửi thông tin hộ chiếu cập nhật trước giờ bay. Quản trị viên cần kiểm tra và xử lý sớm.</small>
        </div>
    </div>
    <a href="{{ route('admin.bookings.index') }}" class="btn btn-warning btn-sm fw-semibold text-dark text-nowrap px-3 py-2 ms-3">
        <i class="bi bi-check2-circle me-1"></i> Xử lý ngay
    </a>
</div>
@endif

{{-- THẺ CHỈ SỐ VẬN HÀNH (OPERATIONAL KPI GRID) --}}
<section class="bf-admin-stat-grid" aria-label="Chỉ số vận hành">
    {{-- KPI 1: Doanh thu --}}
    <article>
        <div class="d-flex justify-content-between align-items-start">
            <span>Doanh thu thành công</span>
            <div class="p-2 rounded-2 bg-success bg-opacity-10 text-success">
                <i class="bi bi-cash-coin fs-5"></i>
            </div>
        </div>
        <strong>{{ number_format($totalRevenue, 0, ',', '.') }} <small class="fs-6 text-muted fw-normal">VND</small></strong>
        <small class="text-success"><i class="bi bi-check-circle me-1"></i>Thanh toán qua cổng thành công</small>
    </article>

    {{-- KPI 2: Tổng đơn đặt vé --}}
    <article>
        <div class="d-flex justify-content-between align-items-start">
            <span>Tổng đơn đặt vé</span>
            <div class="p-2 rounded-2 bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-ticket-perforated fs-5"></i>
            </div>
        </div>
        <strong>{{ number_format($totalBookings) }} <small class="fs-6 text-muted fw-normal">đơn</small></strong>
        <small>
            <span class="text-success fw-semibold">{{ $paidBookingsCount }} đã thanh toán</span> &bull; 
            <span class="text-warning fw-semibold">{{ $pendingBookingsCount }} chờ xử lý</span>
        </small>
    </article>

    {{-- KPI 3: Chuyến bay --}}
    <article>
        <div class="d-flex justify-content-between align-items-start">
            <span>Chuyến bay hôm nay</span>
            <div class="p-2 rounded-2 bg-info bg-opacity-10 text-info">
                <i class="bi bi-airplane fs-5"></i>
            </div>
        </div>
        <strong>{{ number_format($todayFlightsCount) }} <small class="fs-6 text-muted fw-normal">chuyến</small></strong>
        <small class="text-muted">Tổng {{ number_format($totalFlights) }} chuyến bay trong lịch</small>
    </article>

    {{-- KPI 4: Tỷ lệ lấp đầy ghế --}}
    <article>
        <div class="d-flex justify-content-between align-items-start">
            <span>Tỷ lệ lấp đầy ghế</span>
            <div class="p-2 rounded-2 bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-pie-chart fs-5"></i>
            </div>
        </div>
        <strong>{{ $occupancyRate }}%</strong>
        <small class="text-muted">{{ number_format($bookedSeats) }} / {{ number_format($totalSeats) }} ghế đã đặt</small>
    </article>
</section>

{{-- THANH THAO TÁC NHANH (QUICK ACTIONS) --}}
<div class="d-flex flex-wrap gap-2 my-4">
    <a href="{{ route('admin.flights.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Thêm chuyến bay mới
    </a>
    <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-search me-1"></i> Tra cứu đơn đặt vé
    </a>
    <a href="{{ route('admin.airlines.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-building me-1"></i> Quản lý hãng bay
    </a>
    <a href="{{ route('admin.airports.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-geo-alt me-1"></i> Quản lý sân bay
    </a>
    <a href="{{ route('admin.baggage-addons.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-luggage me-1"></i> Cấu hình gói hành lý
    </a>
</div>

{{-- HÀNG BIỂU ĐỒ (CHARTS SECTION) --}}
<section class="bf-admin-grid">
    {{-- Biểu đồ Doanh thu theo tháng --}}
    <div class="bf-admin-panel bf-admin-panel--wide">
        <div class="bf-panel-heading d-flex justify-content-between align-items-center">
            <div>
                <span class="bf-eyebrow">Xu hướng tài chính</span>
                <h2 class="h5 mb-0">Doanh thu vé máy bay theo tháng</h2>
            </div>
            <span class="badge bg-light text-muted border">Năm {{ date('Y') }}</span>
        </div>
        <div style="position: relative; height: 260px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Biểu đồ Tròn Tỷ lệ lấp đầy ghế --}}
    <div class="bf-admin-panel">
        <div class="bf-panel-heading">
            <div>
                <span class="bf-eyebrow">Tải trọng thương mại</span>
                <h2 class="h5 mb-0">Tỷ lệ lấp đầy ghế</h2>
            </div>
        </div>
        <div style="max-width: 200px; margin: 15px auto;">
            <canvas id="occupancyChart"></canvas>
        </div>
        <div class="d-flex justify-content-around text-center pt-3 border-top mt-2">
            <div>
                <small class="text-muted d-block">Đã đặt</small>
                <strong class="text-success">{{ number_format($bookedSeats) }}</strong>
            </div>
            <div class="vr"></div>
            <div>
                <small class="text-muted d-block">Còn trống</small>
                <strong class="text-secondary">{{ number_format(max(0, $totalSeats - $bookedSeats)) }}</strong>
            </div>
        </div>
    </div>

    {{-- Biểu đồ Chuyến bay theo hãng --}}
    <div class="bf-admin-panel bf-admin-panel--full">
        <div class="bf-panel-heading">
            <div>
                <span class="bf-eyebrow">Phân bổ đối tác</span>
                <h2 class="h5 mb-0">Số lượng chuyến bay theo từng hãng hàng không</h2>
            </div>
        </div>
        <div style="position: relative; height: 200px;">
            <canvas id="airlineFlightChart"></canvas>
        </div>
    </div>
</section>

{{-- BẢNG VẬN HÀNH: ĐƠN ĐẶT VÉ MỚI NHẤT & YÊU CẦU DUYỆT HỘ CHIẾU --}}
<div class="row g-4 mt-1">
    {{-- Đơn đặt vé mới nhất --}}
    <div class="col-lg-{{ $pendingPassportRequests->isNotEmpty() ? '8' : '12' }}">
        <div class="bf-admin-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="bf-eyebrow">Giao dịch gần đây</span>
                    <h2 class="h5 mb-0">Đơn đặt vé mới nhất</h2>
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-link text-decoration-none">
                    Xem tất cả <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Chặng bay</th>
                            <th class="text-end">Tổng tiền</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBookings as $booking)
                        @php
                            $firstFlight = $booking->bookingFlights->first()?->flight;
                            $dep = $firstFlight?->departureAirport?->iata_code ?? '---';
                            $arr = $firstFlight?->arrivalAirport?->iata_code ?? '---';
                            $legsCount = $booking->bookingFlights->count();
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="fw-semibold text-primary font-monospace text-decoration-none">
                                    #BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $booking->created_at?->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $booking->user?->name ?? 'Khách vãng lai' }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.72rem; max-width: 140px;">{{ $booking->user?->email }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $dep }} &rarr; {{ $arr }}
                                    @if($legsCount > 1)
                                        <small class="text-muted">({{ $legsCount }} chặng)</small>
                                    @endif
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-nowrap">
                                {{ number_format($booking->total_amount, 0, ',', '.') }} đ
                            </td>
                            <td class="text-center">
                                @if($booking->status === 'paid')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Đã thanh toán</span>
                                @elseif($booking->status === 'cancelled')
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Đã hủy</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Chờ thanh toán</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Xem chi tiết đơn">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                Chưa có đơn đặt vé nào trong hệ thống.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Yêu cầu duyệt hộ chiếu (nếu có) --}}
    @if($pendingPassportRequests->isNotEmpty())
    <div class="col-lg-4">
        <div class="bf-admin-panel h-100 border-warning border-opacity-50">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="bf-eyebrow text-warning-emphasis">Cần can thiệp</span>
                    <h2 class="h5 mb-0">Hộ chiếu chờ duyệt</h2>
                </div>
                <span class="badge bg-warning text-dark">{{ $pendingPassportCount }}</span>
            </div>

            <div class="d-flex flex-column gap-3">
                @foreach($pendingPassportRequests as $req)
                <div class="p-3 rounded-2 bg-light border">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong class="text-dark" style="font-size: 0.85rem;">{{ $req->passenger?->full_name ?? 'Hành khách' }}</strong>
                        <a href="{{ route('admin.bookings.show', $req->booking_id) }}" class="small text-primary text-decoration-none">
                            #BK-{{ str_pad($req->booking_id, 5, '0', STR_PAD_LEFT) }}
                        </a>
                    </div>
                    <div class="text-muted small mb-2">
                        Lý do: {{ Str::limit($req->reason, 45) }}
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-secondary" style="font-size: 0.7rem;">{{ $req->created_at?->diffForHumans() }}</small>
                        <a href="{{ route('admin.bookings.show', $req->booking_id) }}" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.75rem;">
                            Kiểm tra <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Revenue Chart
        const revenueEl = document.getElementById('revenueChart');
        if (revenueEl) {
            const revenueCtx = revenueEl.getContext('2d');
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: {!! json_encode($monthlyRevenue) !!},
                        backgroundColor: 'rgba(18, 104, 179, 0.75)',
                        hoverBackgroundColor: 'rgba(18, 104, 179, 1)',
                        borderColor: '#1268b3',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('vi-VN').format(context.raw) + ' VNĐ';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + 'M';
                                    if (value >= 1000) return (value / 1000) + 'k';
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Occupancy Chart
        const occupancyEl = document.getElementById('occupancyChart');
        if (occupancyEl) {
            const occupancyCtx = occupancyEl.getContext('2d');
            new Chart(occupancyCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Đã đặt (%)', 'Trống (%)'],
                    datasets: [{
                        data: [{{ $occupancyRate }}, {{ $emptyRate }}],
                        backgroundColor: ['#16805f', '#e2e8f0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        }
                    },
                    cutout: '72%'
                }
            });
        }

        // 3. Airline Flights Chart
        const airlineEl = document.getElementById('airlineFlightChart');
        if (airlineEl) {
            const airlineCtx = airlineEl.getContext('2d');
            new Chart(airlineCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($airlineNames) !!},
                    datasets: [{
                        label: 'Số lượng chuyến bay',
                        data: {!! json_encode($airlineFlightCounts) !!},
                        backgroundColor: 'rgba(15, 143, 157, 0.7)',
                        borderColor: '#0f8f9d',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
