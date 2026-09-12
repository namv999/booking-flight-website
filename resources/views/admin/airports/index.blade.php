@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý Sân bay</h2>
        <a href="{{ route('admin.airports.create') }}" class="btn btn-primary">Thêm mới</a>
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
                        <th>Tên sân bay</th>
                        <th>Mã IATA Code</th>
                        <th>Thành phố</th>
                        <th>Quốc gia</th>
                        <th>Múi giờ</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($airports as $item)
                    <tr>
                        <td>{{ $airports->firstItem() + $loop->index }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->iata_code }}</td>
                        <td>{{ $item->city }}</td>
                        <td>{{ $item->country }}</td>
                        <td>{{ $item->timezone }}</td>
                        <td>
                            <a href="{{ route('admin.airports.edit', $item) }}" class="btn btn-sm btn-warning">Sửa</a>
                            <form action="{{ route('admin.airports.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Không tìm thấy dữ liệu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="d-flex justify-content-center">
                {{ $airports->links() }}
            </div>
        </div>
    </div>
</div>
@endsection