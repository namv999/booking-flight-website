@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Thêm mới Hạng vé</h2>

    <form action="{{ route('admin.fare-classes.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Tên hạng vé</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="VD: Phổ thông đặc biệt">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Giá cơ bản (Base Price)</label>
                <input type="number" step="0.01" name="base_price" class="form-control @error('base_price') is-invalid @enderror" value="{{ old('base_price') }}" placeholder="0.00">
                @error('base_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Phí chọn ghế (Seat Selection Fee)</label>
                <input type="number" step="0.01" name="seat_selection_fee" class="form-control @error('seat_selection_fee') is-invalid @enderror" value="{{ old('seat_selection_fee', 0) }}" placeholder="0.00">
                @error('seat_selection_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Hành lý ký gửi (Kg)</label>
                <input type="number" name="checked_baggage_kg" class="form-control @error('checked_baggage_kg') is-invalid @enderror" value="{{ old('checked_baggage_kg', 0) }}" placeholder="VD: 20">
                @error('checked_baggage_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Hành lý xách tay (Kg)</label>
                <input type="number" name="carry_on_baggage_kg" class="form-control @error('carry_on_baggage_kg') is-invalid @enderror" value="{{ old('carry_on_baggage_kg', 7) }}" placeholder="VD: 7">
                @error('carry_on_baggage_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Lưu</button>
        <a href="{{ route('admin.fare-classes.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection