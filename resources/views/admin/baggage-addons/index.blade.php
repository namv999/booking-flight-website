@extends('layouts.admin')

@section('title', 'Quản lý Gói hành lý - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="bf-eyebrow">Dịch vụ bổ trợ</span>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Gói hành lý</h1>
            <p class="text-muted small mb-0">Cấu hình định mức trọng lượng và biểu phí hành lý ký gửi cho các chuyến bay.</p>
        </div>
        <a href="{{ route('admin.baggage-addons.create') }}" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-plus-lg me-1"></i> Thêm mới Gói hành lý
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Form tìm kiếm --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.baggage-addons.index') }}" class="row g-2 align-items-center">
                <div class="col-md-9 col-lg-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Tìm theo tên gói hành lý..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Tìm kiếm</button>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.baggage-addons.index') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
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
                            <th class="ps-3" style="width: 70px;">#</th>
                            <th>Tên gói hành lý</th>
                            <th class="text-center" style="width: 180px;">Trọng lượng</th>
                            <th class="text-end" style="width: 180px;">Biểu phí</th>
                            <th class="text-end pe-3" style="width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baggageAddons as $addon)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="p-2 rounded bg-light border text-primary">
                                            <i class="bi bi-luggage fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $addon->name }}</div>
                                            <small class="text-muted">Hành lý ký gửi tiêu chuẩn</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2 fs-6">
                                        <i class="bi bi-speedometer2 me-1"></i> {{ $addon->weight_kg }} kg
                                    </span>
                                </td>
                                <td class="text-end fw-bold font-monospace text-dark">
                                    {{ number_format($addon->price, 0, ',', '.') }} đ
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.baggage-addons.edit', $addon) }}" class="btn btn-outline-secondary" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.baggage-addons.destroy', $addon) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa gói hành lý này?')">
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
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-luggage fs-2 d-block mb-2 text-secondary"></i>
                                    Chưa có gói hành lý nào trong hệ thống.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($baggageAddons->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $baggageAddons->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
