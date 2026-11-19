@csrf
@isset($savedPassenger)
    @method('PUT')
@endisset

@php
    $sp = $savedPassenger ?? null;
    $defaultCccd = $sp?->cccd_number ?? ($sp?->document_type === 'national_id' ? $sp?->document_number : '');
    $defaultCccdIssue = optional($sp?->cccd_issue_date ?? ($sp?->document_type === 'national_id' ? $sp?->document_issue_date : null))->format('Y-m-d');
    $defaultPassport = $sp?->passport_number ?? ($sp?->document_type === 'passport' ? $sp?->document_number : '');
    $defaultPassportCountry = $sp?->passport_issued_country ?? ($sp?->document_type === 'passport' ? $sp?->document_issued_country : 'VNM');
    $defaultPassportIssue = optional($sp?->passport_issue_date ?? ($sp?->document_type === 'passport' ? $sp?->document_issue_date : null))->format('Y-m-d');
    $defaultPassportExpiry = optional($sp?->passport_expiry_date ?? ($sp?->document_type === 'passport' ? $sp?->document_expiry_date : null))->format('Y-m-d');
@endphp

{{-- 1. THÔNG TIN CÁ NHÂN CƠ BẢN --}}
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="card-title mb-0 fw-bold text-navy" style="color: var(--bf-navy);">
            <i class="bi bi-person-vcard text-primary me-2"></i>Thông tin cá nhân
        </h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-navy" for="full_name">
                    Họ và tên <span class="text-danger">*</span>
                </label>
                <input type="text"
                       name="full_name"
                       id="full_name"
                       class="form-control @error('full_name') is-invalid @enderror"
                       value="{{ old('full_name', $sp?->full_name ?? '') }}"
                       placeholder="Ví dụ: NGUYEN VAN A"
                       required>
                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text small text-muted">Họ và tên đầy đủ theo giấy tờ tùy thân.</div>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-bold small text-navy" for="date_of_birth">
                    Ngày sinh <span class="text-danger">*</span>
                </label>
                <input type="date"
                       name="date_of_birth"
                       id="date_of_birth"
                       class="form-control @error('date_of_birth') is-invalid @enderror"
                       value="{{ old('date_of_birth', optional($sp?->date_of_birth ?? null)->format('Y-m-d')) }}"
                       max="{{ date('Y-m-d') }}"
                       required>
                @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-bold small text-navy d-block">
                    Giới tính <span class="text-danger">*</span>
                </label>
                <div class="d-flex align-items-center gap-3 pt-2">
                    @foreach (['male' => 'Nam', 'female' => 'Nữ'] as $value => $label)
                        <div class="form-check">
                            <input class="form-check-input @error('gender') is-invalid @enderror"
                                   type="radio"
                                   name="gender"
                                   id="gender_{{ $value }}"
                                   value="{{ $value }}"
                                   @checked(old('gender', $sp?->gender ?? '') === $value)
                                   required>
                            <label class="form-check-label small" for="gender_{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-bold small text-navy" for="nationality">
                    Quốc tịch <span class="text-danger">*</span>
                </label>
                <select name="nationality"
                        id="nationality"
                        class="form-select @error('nationality') is-invalid @enderror"
                        required>
                    <option value="">-- Chọn quốc gia --</option>
                    @foreach (config('countries') as $code => $name)
                        <option value="{{ $code }}" @selected(old('nationality', $sp?->nationality ?? 'VNM') === $code)>
                            {{ $name }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
                @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-bold small text-navy" for="passenger_type_default">
                    Loại hành khách mặc định <span class="text-danger">*</span>
                </label>
                <select name="passenger_type_default"
                        id="passenger_type_default"
                        class="form-select @error('passenger_type_default') is-invalid @enderror"
                        required>
                    @foreach (['adult' => 'Người lớn (từ 12 tuổi)', 'child' => 'Trẻ em (2 - 11 tuổi)', 'infant' => 'Em bé (dưới 2 tuổi)'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('passenger_type_default', $sp?->passenger_type_default ?? 'adult') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('passenger_type_default') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-bold small text-navy" for="relationship">
                    Mối quan hệ (ghi chú)
                </label>
                <input type="text"
                       name="relationship"
                       id="relationship"
                       class="form-control @error('relationship') is-invalid @enderror"
                       value="{{ old('relationship', $sp?->relationship ?? '') }}"
                       placeholder="Bản thân, Vợ/Chồng, Con, Đồng nghiệp...">
                @error('relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

{{-- 2. THÔNG TIN CCCD / ĐỊNH DANH (BAY NỘI ĐỊA) --}}
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <h5 class="card-title mb-0 fw-bold text-navy" style="color: var(--bf-navy);">
                <i class="bi bi-card-heading text-success me-2"></i>Căn cước công dân (CCCD / Định danh)
            </h5>
            <small class="text-muted">Sử dụng để tự động điền khi đặt vé các chuyến bay <strong>nội địa</strong>.</small>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Bay nội địa</span>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-navy" for="cccd_number">
                    Số CCCD / Số định danh cá nhân
                </label>
                <input type="text"
                       name="cccd_number"
                       id="cccd_number"
                       class="form-control font-monospace @error('cccd_number') is-invalid @enderror"
                       value="{{ old('cccd_number', $defaultCccd) }}"
                       placeholder="Ví dụ: 001234567890 (12 số)">
                @error('cccd_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text small text-muted">Số thẻ Căn cước công dân hoặc số định danh cá nhân ghi trên giấy khai sinh.</div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-navy" for="cccd_issue_date">
                    Ngày cấp CCCD
                </label>
                <input type="date"
                       name="cccd_issue_date"
                       id="cccd_issue_date"
                       class="form-control @error('cccd_issue_date') is-invalid @enderror"
                       value="{{ old('cccd_issue_date', $defaultCccdIssue) }}"
                       max="{{ date('Y-m-d') }}">
                @error('cccd_issue_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text small text-muted">Ngày cấp ghi ở mặt sau của thẻ CCCD.</div>
            </div>
        </div>
    </div>
</div>

{{-- 3. THÔNG TIN HỘ CHIẾU (BAY QUỐC TẾ) --}}
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <h5 class="card-title mb-0 fw-bold text-navy" style="color: var(--bf-navy);">
                <i class="bi bi-passport text-primary me-2"></i>Hộ chiếu (Passport)
            </h5>
            <small class="text-muted">Sử dụng để tự động điền khi đặt vé các chuyến bay <strong>quốc tế</strong>.</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Bay quốc tế</span>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-navy" for="passport_number">
                    Số Hộ chiếu
                </label>
                <input type="text"
                       name="passport_number"
                       id="passport_number"
                       class="form-control font-monospace @error('passport_number') is-invalid @enderror"
                       value="{{ old('passport_number', $defaultPassport) }}"
                       placeholder="Ví dụ: C1234567">
                @error('passport_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text small text-muted">6 đến 9 ký tự chữ và số (ví dụ: B1234567 hoặc C1234567).</div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-navy" for="passport_issued_country">
                    Quốc gia cấp Hộ chiếu
                </label>
                <select name="passport_issued_country"
                        id="passport_issued_country"
                        class="form-select @error('passport_issued_country') is-invalid @enderror">
                    <option value="">-- Chọn quốc gia --</option>
                    @foreach (config('countries') as $code => $name)
                        <option value="{{ $code }}" @selected(old('passport_issued_country', $defaultPassportCountry) === $code)>
                            {{ $name }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
                @error('passport_issued_country') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-navy" for="passport_issue_date">
                    Ngày cấp Hộ chiếu
                </label>
                <input type="date"
                       name="passport_issue_date"
                       id="passport_issue_date"
                       class="form-control @error('passport_issue_date') is-invalid @enderror"
                       value="{{ old('passport_issue_date', $defaultPassportIssue) }}"
                       max="{{ date('Y-m-d') }}">
                @error('passport_issue_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-navy" for="passport_expiry_date">
                    Ngày hết hạn Hộ chiếu
                </label>
                <input type="date"
                       name="passport_expiry_date"
                       id="passport_expiry_date"
                       class="form-control @error('passport_expiry_date') is-invalid @enderror"
                       value="{{ old('passport_expiry_date', $defaultPassportExpiry) }}">
                @error('passport_expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mb-4">
    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold text-white shadow-sm">
        <i class="bi bi-save me-1"></i> Lưu hồ sơ hành khách
    </button>
    <a href="{{ route('saved-passengers.index') }}" class="btn btn-outline-secondary px-3 py-2">
        Hủy
    </a>
</div>
