@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Chi tiết hãng hàng không: {{ $airline->name }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Mã Code:</strong> {{ $airline->code }}</p>
            <p><strong>Quốc gia:</strong> {{ $airline->country }}</p>
            <p><strong>Logo URL:</strong> {{ $airline->logo_url }}</p>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.airlines.edit', $airline) }}" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-pencil me-1"></i> Chỉnh sửa
        </a>
        <a href="{{ route('admin.airlines.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
        </a>
    </div>
</div>
@endsection