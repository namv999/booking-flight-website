@extends('layouts.admin')

@section('title', 'Quản lý gói hành lý')

@section('content')
    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Quản lý gói hành lý</h1>
        <a href="{{ route('admin.baggage-addons.create') }}" class="btn btn-primary">+ Thêm gói</a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tên gói</th>
                <th>Trọng lượng (kg)</th>
                <th>Giá</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baggageAddons as $addon)
                <tr>
                    <td>{{ $addon->name }}</td>
                    <td>{{ $addon->weight_kg }}</td>
                    <td>{{ number_format($addon->price, 0, ',', '.') }} đ</td>
                    <td>
                        <a href="{{ route('admin.baggage-addons.edit', $addon) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.baggage-addons.destroy', $addon) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Xóa gói này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Chưa có gói hành lý nào</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $baggageAddons->links() }}
@endsection
