@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Thêm sân bay mới</h2>

    <form action="{{ route('admin.airports.store') }}" method="POST">
        @csrf
        <div class="mb-3">
    <label class="form-label">Mã IATA Code</label>
    <input type="text" name="iata_code" class="form-control @error('iata_code') is-invalid @enderror" value="{{ old('iata_code', $airport->iata_code ?? '') }}">
    @error('iata_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Tên sân bay</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $airport->name ?? '') }}">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Thành phố</label>
    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $airport->city ?? '') }}">
    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Quốc gia</label>
    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $airport->country ?? '') }}">
    @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Múi giờ (Timezone)</label>
    <input type="text" name="timezone" class="form-control @error('timezone') is-invalid @enderror" value="{{ old('timezone', $airport->timezone ?? '') }}" placeholder="Ví dụ: Asia/Ho_Chi_Minh">
    @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="{{ route('admin.airports.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection