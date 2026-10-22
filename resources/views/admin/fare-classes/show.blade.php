@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Chi tiết hạng vé: {{ $fareClass->name }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Tên hạng vé:</strong> {{ $fareClass->name }}</p>
            <p><strong>Giá cơ bản:</strong> {{ number_format($fareClass->base_price, 0, ',', '.') }} VNĐ</p>
            <p><strong>Phí chọn ghế:</strong> {{ number_format($fareClass->seat_selection_fee, 0, ',', '.') }} VNĐ</p>
            <p><strong>Hành lý ký gửi:</strong> {{ $fareClass->checked_baggage_kg }} kg</p>
            <p><strong>Hành lý xách tay:</strong> {{ $fareClass->carry_on_baggage_kg }} kg</p>
            <p><strong>Mô tả:</strong> {{ $fareClass->description ?? 'Không có mô tả' }}</p>
            <p><strong>Ngày tạo:</strong> {{ $fareClass->created_at?->format('d/m/Y H:i') }}</p>
            <p><strong>Cập nhật lần cuối:</strong> {{ $fareClass->updated_at?->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.fare-classes.edit', $fareClass) }}" class="btn btn-warning">Sửa</a>
    <a href="{{ route('admin.fare-classes.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
</div>
@endsection
