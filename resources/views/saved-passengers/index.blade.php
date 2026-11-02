@extends('layouts.app')

@section('title', 'Hồ sơ hành khách đã lưu - Jet Charter')
@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
<div class="bf-container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Tài khoản</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hồ sơ hành khách</li>
        </ol>
    </nav>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="status">
            <i class="bi bi-check-circle-fill flex-shrink-0 fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--bf-navy);">Hồ sơ hành khách đã lưu</h1>
            <p class="text-muted small mb-0">Quản lý danh sách người bay để tự động điền nhanh chóng khi đặt vé.</p>
        </div>
        <a href="{{ route('saved-passengers.create') }}" class="btn btn-primary px-3 py-2 fw-bold text-white shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Thêm hồ sơ mới
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3">Họ và tên</th>
                        <th class="py-3">Số giấy tờ</th>
                        <th class="py-3">Ngày sinh</th>
                        <th class="py-3">Quốc tịch</th>
                        <th class="py-3">Loại hành khách</th>
                        <th class="pe-4 py-3 text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($savedPassengers as $passenger)
                        @php
                            $typeLabel = match($passenger->passenger_type_default) {
                                'child' => 'Trẻ em',
                                'infant' => 'Em bé',
                                default => 'Người lớn',
                            };
                            $badgeClass = match($passenger->passenger_type_default) {
                                'child' => 'bg-info-subtle text-info-emphasis border-info-subtle',
                                'infant' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                default => 'bg-primary-subtle text-primary-emphasis border-primary-subtle',
                            };
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <strong class="text-navy">{{ $passenger->full_name }}</strong>
                            </td>
                            <td>
                                @if ($passenger->document_number)
                                    <span class="font-monospace">{{ $passenger->document_number }}</span>
                                    <span class="text-muted small d-block" style="font-size: 0.75rem;">
                                        {{ $passenger->document_type === 'passport' ? 'Hộ chiếu' : 'CCCD / Định danh' }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ optional($passenger->date_of_birth)->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $passenger->nationality_label ?? '-' }}</td>
                            <td>
                                <span class="badge border {{ $badgeClass }} px-2 py-1">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="pe-4 text-end text-nowrap">
                                <a href="{{ route('saved-passengers.edit', $passenger) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil me-1"></i>Sửa
                                </a>
                                <form action="{{ route('saved-passengers.destroy', $passenger) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa hồ sơ hành khách này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-people text-muted fs-1 mb-2 d-block"></i>
                                <div class="fw-bold text-navy mb-1">Chưa có hồ sơ hành khách nào</div>
                                <p class="text-muted small mb-3">Lưu thông tin hành khách thường xuyên bay cùng bạn để đặt vé nhanh hơn.</p>
                                <a href="{{ route('saved-passengers.create') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Tạo hồ sơ đầu tiên
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($savedPassengers->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $savedPassengers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
