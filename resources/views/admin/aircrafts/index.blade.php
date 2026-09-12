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

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Hãng hàng không</th>
                        <th>Model Máy bay</th>
                        <th>Sức chứa (Số ghế)</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aircrafts as $item)
                    <tr>
                        <td>{{ $item->model }}</td>
                        <td>{{ $item->registration_number }}</td>
                        <td>{{ $item->total_seats }}</td>
                        <td>
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
                        <td colspan="5" class="text-center">Không tìm thấy dữ liệu.</td>
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