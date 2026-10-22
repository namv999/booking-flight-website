@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Chi tiết máy bay: {{ $aircraft->model }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Model:</strong> {{ $aircraft->model }}</p>
            <p><strong>Số hiệu đăng ký:</strong> {{ $aircraft->registration_number }}</p>
            <p><strong>Hãng hàng không:</strong> {{ optional($aircraft->airline)->name ?? 'Chưa xác định' }}</p>
            <p><strong>Tổng số ghế:</strong> {{ $aircraft->total_seats }}</p>
            <p><strong>Ngày tạo:</strong> {{ $aircraft->created_at?->format('d/m/Y H:i') }}</p>
            <p><strong>Cập nhật lần cuối:</strong> {{ $aircraft->updated_at?->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.aircrafts.edit', $aircraft) }}" class="btn btn-warning">Sửa</a>
    <a href="{{ route('admin.aircrafts.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
</div>
@endsection
