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

    {{-- Form tìm kiếm, bộ lọc & sắp xếp --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.flights.index') }}">
                <div class="row g-2 mb-2">
                    <div class="col-12 col-lg-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm theo mã (#FL-0001), sân bay, thành phố, máy bay hoặc hãng bay..." value="{{ $search ?? '' }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <select name="sort" class="form-select" aria-label="Sắp xếp danh sách">
                            <option value="latest" @selected(($sort ?? '') === 'latest')>Sắp xếp: Mới tạo gần nhất</option>
                            <option value="oldest" @selected(($sort ?? '') === 'oldest')>Sắp xếp: Cũ nhất</option>
                            <option value="departure_asc" @selected(($sort ?? '') === 'departure_asc')>Khởi hành: Sớm nhất trước</option>
                            <option value="departure_desc" @selected(($sort ?? '') === 'departure_desc')>Khởi hành: Muộn nhất trước</option>
                            <option value="id_asc" @selected(($sort ?? '') === 'id_asc')>Mã chuyến: Tăng dần (A-Z)</option>
                            <option value="id_desc" @selected(($sort ?? '') === 'id_desc')>Mã chuyến: Giảm dần (Z-A)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        @if(!empty($search) || !empty($status) || !empty($departureAirportId) || !empty($arrivalAirportId) || !empty($airlineId) || !empty($departureDate) || (($sort ?? 'latest') !== 'latest'))
                            <a href="{{ route('admin.flights.index') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="row g-2 pt-2 border-top">
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Trạng thái</label>
                        <select name="status" class="form-select form-select-sm" aria-label="Trạng thái chuyến bay">
                            <option value="">Tất cả trạng thái</option>
                            <option value="scheduled" @selected(($status ?? '') === 'scheduled')>Đúng lịch</option>
                            <option value="delayed" @selected(($status ?? '') === 'delayed')>Hoãn / trễ</option>
                            <option value="cancelled" @selected(($status ?? '') === 'cancelled')>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Hãng bay</label>
                        <select name="airline_id" class="form-select form-select-sm" aria-label="Hãng hàng không">
                            <option value="">Tất cả hãng bay</option>
                            @foreach($airlines as $airline)
                                <option value="{{ $airline->id }}" @selected(($airlineId ?? '') == $airline->id)>{{ $airline->name }} ({{ $airline->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Sân bay đi</label>
                        <select name="departure_airport_id" class="form-select form-select-sm" aria-label="Sân bay khởi hành">
                            <option value="">Tất cả sân bay đi</option>
                            @foreach($airports as $airport)
                                <option value="{{ $airport->id }}" @selected(($departureAirportId ?? '') == $airport->id)>{{ $airport->iata_code }} - {{ $airport->city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Sân bay đến</label>
                        <select name="arrival_airport_id" class="form-select form-select-sm" aria-label="Sân bay đến">
                            <option value="">Tất cả sân bay đến</option>
                            @foreach($airports as $airport)
                                <option value="{{ $airport->id }}" @selected(($arrivalAirportId ?? '') == $airport->id)>{{ $airport->iata_code }} - {{ $airport->city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Ngày khởi hành</label>
                        <input type="date" name="departure_date" class="form-control form-control-sm" value="{{ $departureDate ?? '' }}">
                    </div>
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
