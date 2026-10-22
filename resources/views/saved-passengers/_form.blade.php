@csrf
@isset($savedPassenger)
    @method('PUT')
@endisset

<div class="mb-3">
    <label class="form-label">Họ tên</label>
    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
           value="{{ old('full_name', $savedPassenger->full_name ?? '') }}">
    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Số giấy tờ (CCCD/Hộ chiếu)</label>
    <input type="text" name="document_number" class="form-control @error('document_number') is-invalid @enderror"
           value="{{ old('document_number', $savedPassenger->document_number ?? '') }}">
    @error('document_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Ngày sinh</label>
    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
           value="{{ old('date_of_birth', optional($savedPassenger->date_of_birth ?? null)->format('Y-m-d')) }}">
    @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Loại hành khách mặc định</label>
    <select name="passenger_type_default" class="form-select @error('passenger_type_default') is-invalid @enderror">
        @foreach (['adult' => 'Người lớn', 'child' => 'Trẻ em', 'infant' => 'Em bé'] as $value => $label)
            <option value="{{ $value }}" @selected(old('passenger_type_default', $savedPassenger->passenger_type_default ?? '') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('passenger_type_default') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Quan hệ (ghi chú, không bắt buộc)</label>
    <input type="text" name="relationship" class="form-control @error('relationship') is-invalid @enderror"
           value="{{ old('relationship', $savedPassenger->relationship ?? '') }}">
    @error('relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<button type="submit" class="btn btn-primary">Lưu</button>
<a href="{{ route('saved-passengers.index') }}" class="btn btn-secondary">Hủy</a>