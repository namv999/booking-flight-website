@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý Hãng hàng không</h2>
        <a href="{{ route('admin.airlines.create') }}" class="btn btn-primary">Thêm mới</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Form tìm kiếm -->
    <form method="GET" action="{{ route('admin.airlines.index') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Tìm theo tên hoặc mã code..." value="{{ $search ?? '' }}">
            <button class="btn btn-outline-secondary" type="submit">Tìm kiếm</button>
            @if(!empty($search))
                <a href="{{ route('admin.airlines.index') }}" class="btn btn-outline-danger">Reset</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Logo</th>
                        <th>Tên hãng</th>
                        <th>Mã Code</th>
                        <th>Quốc gia</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($airlines as $airline)
                    <tr>
                        <td>{{ $airlines->firstItem() + $loop->index }}</td>
                        <td>
                            @if($airline->logo_url)
                                <img src="{{ $airline->logo_url }}" alt="{{ $airline->name }}" width="50">
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $airline->name }}</td>
                        <td>{{ $airline->code }}</td>
                        <td>{{ $airline->country }}</td>
                        <td>
                            <a href="{{ route('admin.airlines.edit', $airline) }}" class="btn btn-sm btn-warning">Sửa</a>
                            <form action="{{ route('admin.airlines.destroy', $airline) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
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

            <!-- Phân trang -->
            <div class="d-flex justify-content-center">
                {{ $airlines->links() }}
            </div>
        </div>
    </div>
</div>
@endsection