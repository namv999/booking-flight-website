@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý Máy bay</h2>
        <a href="{{ route('admin.aircrafts.create') }}" class="btn btn-primary">Thêm mới</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Form tìm kiếm -->
    <form method="GET" action="{{ route('admin.aircrafts.index') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo model máy bay..." value="{{ $search ?? '' }}">
            <button class="btn btn-outline-secondary" type="submit">Tìm kiếm</button>
            @if(!empty($search))
                <a href="{{ route('admin.aircrafts.index') }}" class="btn btn-outline-danger">Reset</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Hãng hàng không</th>
                        <th>Model Máy bay</th>
                        <th>Số hiệu đăng ký</th>
                        <th>Sức chứa (Số ghế)</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aircrafts as $item)
                    <tr>
                        <td>{{ $aircrafts->firstItem() + $loop->index }}</td>
                        <td>{{ optional($item->airline)->name ?? 'Chưa xác định' }}</td>
                        <td>
                            <a href="{{ route('admin.aircrafts.show', $item) }}" class="text-primary fw-semibold text-decoration-none">
                                {{ $item->model }}
                            </a>
                        </td>
                        <td>{{ $item->registration_number }}</td>
                        <td>{{ $item->total_seats }}</td>
                        <td>
                            <a href="{{ route('admin.aircrafts.show', $item) }}" class="btn btn-sm btn-info text-white">Xem</a>
                            <a href="{{ route('admin.aircrafts.edit', $item) }}" class="btn btn-sm btn-warning">Sửa</a>
                            <form action="{{ route('admin.aircrafts.destroy', $item) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Không tìm thấy dữ liệu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="d-flex justify-content-center">
                {{ $aircrafts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection