@extends('layouts.app')

@section('title', 'Hồ sơ hành khách đã lưu')
@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
<div class="bf-container py-4">
    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Hồ sơ hành khách đã lưu</h1>
        <a href="{{ route('saved-passengers.create') }}" class="btn btn-primary">+ Thêm hồ sơ</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Số giấy tờ</th>
                    <th>Ngày sinh</th>
                    <th>Quốc tịch</th>
                    <th>Loại</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($savedPassengers as $passenger)
                    <tr>
                        <td>{{ $passenger->full_name }}</td>
                        <td>{{ $passenger->document_number ?? '-' }}</td>
                        <td>{{ optional($passenger->date_of_birth)->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $passenger->nationality_label ?? '-' }}</td>
                        <td>{{ $passenger->passenger_type_default }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('saved-passengers.edit', $passenger) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                            <form action="{{ route('saved-passengers.destroy', $passenger) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Xóa hồ sơ này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Chưa có hồ sơ nào</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $savedPassengers->links() }}
</div>
@endsection