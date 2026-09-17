@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Chỉnh sửa Chuyến bay</h2>

    <form action="{{ route('admin.flights.update', $flight) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Máy bay</label>
            <select name="aircraft_id" class="form-control @error('aircraft_id') is-invalid @enderror">
                <option value="">-- Chọn máy bay --</option>
                @foreach($aircrafts as $aircraft)
                    <option value="{{ $aircraft->id }}" {{ old('aircraft_id', $flight->aircraft_id) == $aircraft->id ? 'selected' : '' }}>
                        {{ $aircraft->model }} ({{ $aircraft->registration_number }})
                    </option>
                @endforeach
            </select>
            @error('aircraft_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Sân bay xuất phát</label>
                <select name="departure_airport_id" class="form-control @error('departure_airport_id') is-invalid @enderror">
                    <option value="">-- Chọn sân bay đi --</option>
                    @foreach($airports as $airport)
                        <option value="{{ $airport->id }}" {{ old('departure_airport_id', $flight->departure_airport_id) == $airport->id ? 'selected' : '' }}>
                            {{ $airport->name }} ({{ $airport->code }})
                        </option>
                    @endforeach
                </select>
                @error('departure_airport_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Sân bay đến</label>
                <select name="arrival_airport_id" class="form-control @error('arrival_airport_id') is-invalid @enderror">
                    <option value="">-- Chọn sân bay đến --</option>
                    @foreach($airports as $airport)
                        <option value="{{ $airport->id }}" {{ old('arrival_airport_id', $flight->arrival_airport_id) == $airport->id ? 'selected' : '' }}>
                            {{ $airport->name }} ({{ $airport->code }})
                        </option>
                    @endforeach
                </select>
                @error('arrival_airport_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Thời gian đi</label>
                <input type="datetime-local" name="departure_time" class="form-control @error('departure_time') is-invalid @enderror" value="{{ old('departure_time', optional($flight->departure_time)->format('Y-m-d\TH:i')) }}">
                @error('departure_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Thời gian đến</label>
                <input type="datetime-local" name="arrival_time" class="form-control @error('arrival_time') is-invalid @enderror" value="{{ old('arrival_time', optional($flight->arrival_time)->format('Y-m-d\TH:i')) }}">
                @error('arrival_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-control @error('status') is-invalid @enderror">
                <option value="Scheduled" {{ old('status', $flight->status) == 'Scheduled' ? 'selected' : '' }}>Scheduled (Lên lịch)</option>
                <option value="Delayed" {{ old('status', $flight->status) == 'Delayed' ? 'selected' : '' }}>Delayed (Trễ chuyến)</option>
                <option value="Cancelled" {{ old('status', $flight->status) == 'Cancelled' ? 'selected' : '' }}>Cancelled (Hủy)</option>
                <option value="Completed" {{ old('status', $flight->status) == 'Completed' ? 'selected' : '' }}>Completed (Hoàn thành)</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('admin.flights.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection