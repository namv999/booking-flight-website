@extends('layouts.admin')

@section('title', 'Quản lý Chuyến bay - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="bf-eyebrow">Điều phối bay</span>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Chuyến bay</h1>
            <p class="text-muted small mb-0">Theo dõi, tạo mới và cập nhật lịch trình các chặng bay trong toàn mạng lưới.</p>
        </div>
        <a href="{{ route('admin.flights.create') }}" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-plus-lg me-1"></i> Thêm mới Chuyến bay
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Form tìm kiếm --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.flights.index') }}" class="row g-2 align-items-center">
                <div class="col-md-9 col-lg-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm theo tên sân bay, mã IATA (SGN, HAN...) hoặc thành phố..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Tìm kiếm</button>
                    @if(!empty($search))
                        <a href="{{ route('admin.flights.index') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Bảng danh sách chuyến bay --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 90px;">Mã chuyến</th>
                            <th>Máy bay / Hãng</th>
                            <th>Chặng bay (Đi &rarr; Đến)</th>
                            <th>Thời gian cất cánh</th>
                            <th>Thời gian hạ cánh</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-3" style="width: 170px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($flights as $flight)
                            @php
                                $status = strtolower($flight->status ?? 'scheduled');
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('admin.flights.show', $flight) }}" class="text-primary fw-bold text-decoration-none font-monospace">
                                        #FL-{{ str_pad($flight->id, 4, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ optional($flight->aircraft)->model ?? 'Chưa gán máy bay' }}</div>
                                    <div class="small text-muted font-monospace">{{ optional($flight->aircraft)->registration_number }} &bull; {{ optional($flight->aircraft->airline)->name }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            {{ optional($flight->departureAirport)->iata_code }}
                                        </span>
                                        <i class="bi bi-arrow-right text-muted small"></i>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                            {{ optional($flight->arrivalAirport)->iata_code }}
                                        </span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ optional($flight->departureAirport)->city }} &rarr; {{ optional($flight->arrivalAirport)->city }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ optional($flight->departure_time)->format('H:i') }}</div>
                                    <div class="small text-muted">{{ optional($flight->departure_time)->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ optional($flight->arrival_time)->format('H:i') }}</div>
                                    <div class="small text-muted">{{ optional($flight->arrival_time)->format('d/m/Y') }}</div>
                                </td>
                                <td class="text-center">
                                    @if ($status === 'scheduled')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-clock me-1"></i> Scheduled
                                        </span>
                                    @elseif ($status === 'delayed')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Delayed
                                        </span>
                                    @elseif ($status === 'completed')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-check2-circle me-1"></i> Completed
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            {{ ucfirst($status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.flights.show', $flight) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.flights.edit', $flight) }}" class="btn btn-outline-secondary" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa chuyến bay này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Xóa chuyến bay" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-airplane fs-2 d-block mb-2 text-secondary"></i>
                                    Không tìm thấy dữ liệu chuyến bay nào phù hợp.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($flights->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $flights->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
