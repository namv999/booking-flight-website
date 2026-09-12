@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Quản lý Hạng vé</h2>
        <a href="{{ route('admin.fare-classes.create') }}" class="btn btn-primary">Thêm mới Hạng vé</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.fare-classes.index') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo tên hạng vé..." value="{{ $search ?? '' }}">
            <button class="btn btn-outline-secondary" type="submit">Tìm kiếm</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên hạng vé</th>
                        <th>Giá cơ bản</th>
                        <th>Phí chọn ghế</th>
                        <th>Ký gửi</th>
                        <th>Xách tay</th>
                        <th width="150">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fareClasses as $fareClass)
                        <tr>
                            <td>{{ $fareClass->id }}</td>
                            <td>{{ $fareClass->name }}</td>
                            <td>{{ number_format($fareClass->base_price, 2) }}</td>
                            <td>{{ number_format($fareClass->seat_selection_fee, 2) }}</td>
                            <td>{{ $fareClass->checked_baggage_kg }} kg</td>
                            <td>{{ $fareClass->carry_on_baggage_kg }} kg</td>
                            <td>
                                <a href="{{ route('admin.fare-classes.edit', $fareClass) }}" class="btn btn-sm btn-warning">Sửa</a>
                                <form action="{{ route('admin.fare-classes.destroy', $fareClass) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Không có dữ liệu hạng vé nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $fareClasses->links() }}
        </div>
    </div>
</div>
@endsection