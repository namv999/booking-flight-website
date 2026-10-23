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
    <label class="form-label">Loại giấy tờ</label>
    <select name="document_type" class="form-select @error('document_type') is-invalid @enderror">
        <option value="">-- Chọn --</option>
        @foreach (['national_id' => 'CCCD/CMND', 'passport' => 'Hộ chiếu'] as $value => $label)
            <option value="{{ $value }}" @selected(old('document_type', $savedPassenger->document_type ?? '') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('document_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
    <label class="form-label d-block">Giới tính</label>
    @foreach (['male' => 'Nam', 'female' => 'Nữ'] as $value => $label)
        <div class="form-check form-check-inline">
            <input class="form-check-input @error('gender') is-invalid @enderror" type="radio" name="gender"
                   id="gender_{{ $value }}" value="{{ $value }}"
                   @checked(old('gender', $savedPassenger->gender ?? '') === $value)>
            <label class="form-check-label" for="gender_{{ $value }}">{{ $label }}</label>
        </div>
    @endforeach
    @error('gender') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Quốc tịch</label>
    <select name="nationality" class="form-select @error('nationality') is-invalid @enderror">
        <option value="">-- Chọn --</option>
        @foreach (config('countries') as $code => $name)
            <option value="{{ $code }}" @selected(old('nationality', $savedPassenger->nationality ?? '') === $code)>
                {{ $name }} ({{ $code }})
            </option>
        @endforeach
    </select>
    @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Quốc gia cấp giấy tờ</label>
    <select name="document_issued_country" class="form-select @error('document_issued_country') is-invalid @enderror">
        <option value="">-- Chọn --</option>
        @foreach (config('countries') as $code => $name)
            <option value="{{ $code }}" @selected(old('document_issued_country', $savedPassenger->document_issued_country ?? '') === $code)>
                {{ $name }} ({{ $code }})
            </option>
        @endforeach
    </select>
    @error('document_issued_country') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Ngày cấp</label>
    <input type="date" name="document_issue_date" class="form-control @error('document_issue_date') is-invalid @enderror"
           value="{{ old('document_issue_date', optional($savedPassenger->document_issue_date ?? null)->format('Y-m-d')) }}">
    @error('document_issue_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Ngày hết hạn</label>
    <input type="date" name="document_expiry_date" class="form-control @error('document_expiry_date') is-invalid @enderror"
           value="{{ old('document_expiry_date', optional($savedPassenger->document_expiry_date ?? null)->format('Y-m-d')) }}">
    @error('document_expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
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