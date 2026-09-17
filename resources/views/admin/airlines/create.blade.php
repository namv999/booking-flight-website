@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Thêm mới Hãng hàng không</h2>

    <form action="{{ route('admin.airlines.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Tên hãng hàng không</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="VD: Vietnam Airlines">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Mã Code</label>
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="VD: VN">
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Quốc gia</label>
            <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country') }}" placeholder="VD: Vietnam">
            @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Logo URL</label>
            <input type="text" name="logo_url" class="form-control @error('logo_url') is-invalid @enderror" value="{{ old('logo_url') }}" placeholder="VD: https://example.com/logo.png">
            @error('logo_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Lưu</button>
        <a href="{{ route('admin.airlines.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection