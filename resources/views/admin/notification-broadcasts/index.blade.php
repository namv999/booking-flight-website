@extends('layouts.admin')

@section('title', 'Thông báo đã gửi - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0 bf-broadcast-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="bf-eyebrow">Liên lạc khách hàng</span>
            <h1 class="h3 fw-bold text-dark mb-1">Thông báo đã gửi</h1>
            <p class="text-muted small mb-0">Xem lịch sử thông báo chung đã gửi tới khách hàng.</p>
        </div>
        <a href="{{ route('admin.notification-broadcasts.create') }}" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-megaphone me-2"></i>Gửi thông báo mới
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.notification-broadcasts.index') }}" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-2 align-items-center">
            <div class="col-12 col-lg-6">
                <label class="visually-hidden" for="broadcast-search">Tìm kiếm thông báo</label>
                <input id="broadcast-search" type="search" name="search" class="form-control" placeholder="Tìm tiêu đề hoặc nội dung" value="{{ $search ?? '' }}">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="visually-hidden" for="broadcast-type">Loại thông báo</label>
                <select id="broadcast-type" name="type" class="form-select">
                    <option value="">Tất cả loại</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Lọc</button>
                <a href="{{ route('admin.notification-broadcasts.index') }}" class="btn btn-outline-secondary" aria-label="Đặt lại bộ lọc"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th class="ps-3">Thời gian</th><th>Loại</th><th>Tiêu đề và nội dung</th><th>Liên kết</th><th>Người nhận</th><th>Người gửi</th>
                </tr></thead>
                <tbody>
                @forelse ($broadcasts as $b)
                    @php
                        $typeStyle = match ($b->type) {
                            'promotion' => 'bf-notif-type--coral',
                            'announcement' => 'bf-notif-type--info',
                            default => 'bf-notif-type--muted',
                        };
                    @endphp
                    <tr>
                        <td class="ps-3 text-nowrap">{{ $b->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="bf-notif-type {{ $typeStyle }}">{{ $b->type_label }}</span></td>
                        <td class="bf-broadcast-copy"><strong>{{ $b->title }}</strong><span>{{ $b->message }}</span></td>
                        <td><code>{{ $b->url ?: '—' }}</code></td>
                        <td class="text-nowrap">{{ number_format($b->recipient_count) }}</td>
                        <td>{{ $b->admin?->name ?? '---' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-megaphone fs-2 d-block mb-2"></i>Chưa có thông báo chung nào được gửi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($broadcasts->hasPages())
            <div class="p-3 border-top d-flex justify-content-center">{{ $broadcasts->links() }}</div>
        @endif
    </div>
</div>
@endsection
