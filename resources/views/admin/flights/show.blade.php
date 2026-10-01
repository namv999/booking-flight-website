@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Chi tiết chuyến bay #{{ $flight->id }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Mã chuyến bay:</strong> #{{ $flight->id }}</p>
            <p><strong>Máy bay:</strong> {{ optional($flight->aircraft)->model }} ({{ optional($flight->aircraft)->registration_number }})</p>
            <p><strong>Hãng hàng không:</strong> {{ optional(optional($flight->aircraft)->airline)->name ?? 'Chưa xác định' }}</p>
            <p><strong>Sân bay đi:</strong> {{ optional($flight->departureAirport)->name }} ({{ optional($flight->departureAirport)->iata_code }}) - {{ optional($flight->departureAirport)->city }}</p>
            <p><strong>Sân bay đến:</strong> {{ optional($flight->arrivalAirport)->name }} ({{ optional($flight->arrivalAirport)->iata_code }}) - {{ optional($flight->arrivalAirport)->city }}</p>
            <p><strong>Thời gian khởi hành:</strong> {{ optional($flight->departure_time)->format('d/m/Y H:i') }}</p>
            <p><strong>Thời gian hạ cánh:</strong> {{ optional($flight->arrival_time)->format('d/m/Y H:i') }}</p>
            <p>
                <strong>Trạng thái:</strong>
                <span class="badge bg-{{ $flight->status == 'Scheduled' ? 'primary' : ($flight->status == 'Completed' ? 'success' : ($flight->status == 'Cancelled' ? 'danger' : 'warning')) }}">
                    {{ $flight->status }}
                </span>
            </p>
            <p><strong>Ngày tạo:</strong> {{ $flight->created_at?->format('d/m/Y H:i') }}</p>
            <p><strong>Cập nhật lần cuối:</strong> {{ $flight->updated_at?->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <a href="{{ route('admin.flights.edit', $flight) }}" class="btn btn-warning">Sửa</a>
    <a href="{{ route('admin.flights.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
</div>
@endsection
