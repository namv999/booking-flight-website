@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Quản lý Chuyến bay</h2>
        <a href="{{ route('admin.flights.create') }}" class="btn btn-primary">Thêm mới Chuyến bay</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.flights.index') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo sân bay đi hoặc đến..." value="{{ $search ?? '' }}">
            <button class="btn btn-outline-secondary" type="submit">Tìm kiếm</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Máy bay</th>
                        <th>Sân bay đi</th>
                        <th>Sân bay đến</th>
                        <th>Thời gian đi</th>
                        <th>Thời gian đến</th>
                        <th>Trạng thái</th>
                        <th width="150">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flights as $flight)
                        <tr>
                            <td>{{ $flight->id }}</td>
                            <td>{{ optional($flight->aircraft)->model }} ({{ optional($flight->aircraft)->registration_number }})</td>
                            <td>{{ optional($flight->departureAirport)->name }} ({{ optional($flight->departureAirport)->code }})</td>
                            <td>{{ optional($flight->arrivalAirport)->name }} ({{ optional($flight->arrivalAirport)->code }})</td>
                            <td>{{ optional($flight->departure_time)->format('d/m/Y H:i') }}</td>
                            <td>{{ optional($flight->arrival_time)->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $flight->status == 'Scheduled' ? 'primary' : ($flight->status == 'Completed' ? 'success' : 'warning') }}">
                                    {{ $flight->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.flights.edit', $flight) }}" class="btn btn-sm btn-warning">Sửa</a>
                                <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa chuyến bay này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Không có dữ liệu chuyến bay nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $flights->links() }}
        </div>
    </div>
</div>
@endsection