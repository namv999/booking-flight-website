@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Chỉnh sửa thông tin máy bay</h2>

    <form action="{{ route('admin.aircrafts.update', $aircraft) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Hãng hàng không</label>
            <select name="airline_id" class="form-control @error('airline_id') is-invalid @enderror">
                <option value="">-- Chọn hãng hàng không --</option>
                @foreach($airlines as $airline)
                    <option value="{{ $airline->id }}" {{ (old('airline_id', $aircraft->airline_id) == $airline->id) ? 'selected' : '' }}>
                        {{ $airline->name }}
                    </option>
                @endforeach
            </select>
            @error('airline_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Model Máy bay</label>
            <input type="text" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', $aircraft->model) }}">
            @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Số hiệu đăng ký (Registration Number)</label>
            <input type="text" name="registration_number" class="form-control @error('registration_number') is-invalid @enderror" value="{{ old('registration_number', $aircraft->registration_number) }}">
            @error('registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Tổng số ghế</label>
            <input type="number" name="total_seats" class="form-control @error('total_seats') is-invalid @enderror" value="{{ old('total_seats', $aircraft->total_seats) }}">
            @error('total_seats') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('admin.aircrafts.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection