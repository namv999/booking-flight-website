@extends('layouts.admin')

@section('title', 'Quản lý Hạng vé - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="bf-eyebrow">Chính sách thương mại</span>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Hạng vé</h1>
            <p class="text-muted small mb-0">Cấu hình các hạng dịch vụ, quyền lợi hành lý miễn cước và phí chọn chỗ ngồi.</p>
        </div>
        <a href="{{ route('admin.fare-classes.create') }}" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-plus-lg me-1"></i> Thêm mới Hạng vé
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Form tìm kiếm --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.fare-classes.index') }}" class="row g-2 align-items-center">
                <div class="col-md-9 col-lg-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm theo tên hạng vé hoặc mô tả..." value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Tìm kiếm</button>
                    @if(!empty($search))
                        <a href="{{ route('admin.fare-classes.index') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
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
                            <th class="ps-3" style="width: 70px;">ID</th>
                            <th>Tên hạng vé</th>
                            <th class="text-end" style="width: 160px;">Giá cơ bản</th>
                            <th class="text-end" style="width: 160px;">Phí chọn ghế</th>
                            <th class="text-center" style="width: 140px;">Ký gửi</th>
                            <th class="text-center" style="width: 140px;">Xách tay</th>
                            <th class="text-end pe-3" style="width: 170px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fareClasses as $fareClass)
                            <tr>
                                <td class="ps-3 font-monospace text-muted small">#FC-{{ $fareClass->id }}</td>
                                <td>
                                    <a href="{{ route('admin.fare-classes.show', $fareClass) }}" class="text-primary fw-bold text-decoration-none">
                                        {{ $fareClass->name }}
                                    </a>
                                    @if($fareClass->description)
                                        <div class="small text-muted text-truncate" style="max-width: 250px;">{{ $fareClass->description }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold font-monospace">
                                    {{ number_format($fareClass->base_price, 0, ',', '.') }} đ
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    {{ number_format($fareClass->seat_selection_fee, 0, ',', '.') }} đ
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-luggage me-1 text-primary"></i> {{ $fareClass->checked_baggage_kg }} kg
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-bag me-1 text-info"></i> {{ $fareClass->carry_on_baggage_kg }} kg
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.fare-classes.show', $fareClass) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.fare-classes.edit', $fareClass) }}" class="btn btn-outline-secondary" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.fare-classes.destroy', $fareClass) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa hạng vé này?')">
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
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-ticket-perforated fs-2 d-block mb-2 text-secondary"></i>
                                    Không có dữ liệu hạng vé nào phù hợp.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($fareClasses->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $fareClasses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
