@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Chi tiết sân bay: {{ $airport->name }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Mã IATA Code:</strong> {{ $airport->iata_code }}</p>
            <p><strong>Tên sân bay:</strong> {{ $airport->name }}</p>
            <p><strong>Thành phố:</strong> {{ $airport->city }}</p>
            <p><strong>Quốc gia:</strong> {{ $airport->country }}</p>
            <p><strong>Múi giờ (Timezone):</strong> {{ $airport->timezone }}</p>
            <p><strong>Ngày tạo:</strong> {{ $airport->created_at?->format('d/m/Y H:i') }}</p>
            <p><strong>Cập nhật lần cuối:</strong> {{ $airport->updated_at?->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.airports.edit', $airport) }}" class="btn btn-warning">Sửa</a>
    <a href="{{ route('admin.airports.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
</div>
@endsection
