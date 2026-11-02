@extends('layouts.admin')

@section('title', 'Quản lý Máy bay - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="bf-eyebrow">Đội bay & Phương tiện</span>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Máy bay</h1>
            <p class="text-muted small mb-0">Theo dõi thông tin chi tiết các tàu bay, số hiệu đăng ký và sơ đồ ghế ngồi.</p>
        </div>
        <a href="{{ route('admin.aircrafts.create') }}" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-plus-lg me-1"></i> Thêm mới Máy bay
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Form tìm kiếm -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.aircrafts.index') }}" class="row g-2 align-items-center">
                <div class="col-md-9 col-lg-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm theo dòng máy bay (Airbus A321, Boeing 787...)..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Tìm kiếm</button>
                    @if(!empty($search))
                        <a href="{{ route('admin.aircrafts.index') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
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
                            <th class="ps-3" style="width: 60px;">#</th>
                            <th>Hãng hàng không</th>
                            <th>Dòng máy bay (Model)</th>
                            <th style="width: 170px;">Số hiệu đăng ký</th>
                            <th class="text-center" style="width: 150px;">Sức chứa</th>
                            <th class="text-end pe-3" style="width: 170px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($aircrafts as $item)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $aircrafts->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ optional($item->airline)->name ?? 'Chưa xác định' }}</div>
                                <small class="text-muted font-monospace">{{ optional($item->airline)->code }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.aircrafts.show', $item) }}" class="text-primary fw-bold text-decoration-none">
                                    {{ $item->model }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-6">
                                    {{ $item->registration_number }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                    <i class="bi bi-people me-1"></i> {{ $item->total_seats }} ghế
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.aircrafts.show', $item) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.aircrafts.edit', $item) }}" class="btn btn-outline-secondary" title="Chỉnh sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.aircrafts.destroy', $item) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa máy bay này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Xóa" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-airplane fs-2 d-block mb-2 text-secondary"></i>
                                Không tìm thấy dữ liệu máy bay nào.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($aircrafts->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $aircrafts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
