@php
    $cfg = match ($group) {
        'children' => [
            'name_placeholder' => 'Ví dụ: NGUYEN VAN B',
            'domestic_name_hint' => 'Như trên giấy khai sinh hoặc hộ chiếu',
            'domestic_doc_label' => 'Số giấy tờ (nếu có)',
            'domestic_doc_placeholder' => 'Số định danh / GKS',
        ],
        'infants' => [
            'name_placeholder' => 'Ví dụ: NGUYEN THI C',
            'domestic_name_hint' => null,
            'domestic_doc_label' => 'Số giấy khai sinh',
            'domestic_doc_placeholder' => 'GKS',
        ],
        default => [
            'name_placeholder' => 'Ví dụ: NGUYEN VAN A',
            'domestic_name_hint' => 'Như trên giấy tờ tùy thân (CCCD/Hộ chiếu)',
            'domestic_doc_label' => 'Số CCCD / Hộ chiếu',
            'domestic_doc_placeholder' => '001234567890',
        ],
    };
@endphp

@if ($isInternational)
    <input type="hidden" name="{{ $group }}[{{ $i }}][document_type]" value="passport">
@endif

<div class="row g-3">
    {{-- điểm mở rộng autofill (Nguyên) --}}
    @includeIf('booking.partials.passenger-autofill', ['group' => $group, 'i' => $i])

    @if ($isInternational)
        <div class="col-12 col-md-6">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_name">
                Họ và tên <span class="text-danger">*</span>
            </label>
            <input type="text"
                   class="form-control bf-input @error("$group.$i.full_name") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_name"
                   name="{{ $group }}[{{ $i }}][full_name]"
                   value="{{ old("$group.$i.full_name") }}"
                   placeholder="{{ $cfg['name_placeholder'] }}"
                   required>
            <div class="form-text small text-muted" style="font-size: 0.74rem;">Như trên hộ chiếu, viết không dấu, ví dụ NGUYEN VAN A</div>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_doc">
                Số hộ chiếu <span class="text-danger">*</span>
            </label>
            <input type="text"
                   class="form-control bf-input @error("$group.$i.document_number") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_doc"
                   name="{{ $group }}[{{ $i }}][document_number]"
                   value="{{ old("$group.$i.document_number") }}"
                   placeholder="C1234567"
                   required>
            <div class="form-text small text-muted" style="font-size: 0.74rem;">6 đến 9 ký tự chữ và số, ví dụ C1234567</div>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_dob">
                Ngày sinh <span class="text-danger">*</span>
            </label>
            <input type="date"
                   class="form-control bf-input @error("$group.$i.date_of_birth") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_dob"
                   name="{{ $group }}[{{ $i }}][date_of_birth]"
                   value="{{ old("$group.$i.date_of_birth") }}"
                   max="{{ date('Y-m-d') }}"
                   required>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-bold small text-navy d-block">
                Giới tính <span class="text-danger">*</span>
            </label>
            <div class="d-flex align-items-center gap-3 pt-1">
                <div class="form-check">
                    <input class="form-check-input @error("$group.$i.gender") is-invalid @enderror"
                           type="radio"
                           name="{{ $group }}[{{ $i }}][gender]"
                           id="{{ $group }}_{{ $i }}_gender_male"
                           value="male"
                           {{ old("$group.$i.gender") === 'male' ? 'checked' : '' }}
                           required>
                    <label class="form-check-label small" for="{{ $group }}_{{ $i }}_gender_male">Nam</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input @error("$group.$i.gender") is-invalid @enderror"
                           type="radio"
                           name="{{ $group }}[{{ $i }}][gender]"
                           id="{{ $group }}_{{ $i }}_gender_female"
                           value="female"
                           {{ old("$group.$i.gender") === 'female' ? 'checked' : '' }}
                           required>
                    <label class="form-check-label small" for="{{ $group }}_{{ $i }}_gender_female">Nữ</label>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_nat">
                Quốc tịch <span class="text-danger">*</span>
            </label>
            <select class="form-select bf-input @error("$group.$i.nationality") is-invalid @enderror"
                    id="{{ $group }}_{{ $i }}_nat"
                    name="{{ $group }}[{{ $i }}][nationality]"
                    required>
                <option value="">-- Chọn quốc gia --</option>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" {{ old("$group.$i.nationality", 'VNM') === $code ? 'selected' : '' }}>
                        {{ $name }} ({{ $code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_issued_country">
                Quốc gia cấp <span class="text-danger">*</span>
            </label>
            <select class="form-select bf-input @error("$group.$i.document_issued_country") is-invalid @enderror"
                    id="{{ $group }}_{{ $i }}_issued_country"
                    name="{{ $group }}[{{ $i }}][document_issued_country]"
                    required>
                <option value="">-- Chọn quốc gia --</option>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" {{ old("$group.$i.document_issued_country", 'VNM') === $code ? 'selected' : '' }}>
                        {{ $name }} ({{ $code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_issue_date">
                Ngày cấp <span class="text-danger">*</span>
            </label>
            <input type="date"
                   class="form-control bf-input @error("$group.$i.document_issue_date") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_issue_date"
                   name="{{ $group }}[{{ $i }}][document_issue_date]"
                   value="{{ old("$group.$i.document_issue_date") }}"
                   max="{{ date('Y-m-d') }}"
                   required>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_expiry_date">
                Ngày hết hạn <span class="text-danger">*</span>
            </label>
            <input type="date"
                   class="form-control bf-input @error("$group.$i.document_expiry_date") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_expiry_date"
                   name="{{ $group }}[{{ $i }}][document_expiry_date]"
                   value="{{ old("$group.$i.document_expiry_date") }}"
                   required>
        </div>
    @else
        <div class="col-12 col-md-6">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_name">
                Họ và tên <span class="text-danger">*</span>
            </label>
            <input type="text"
                   class="form-control bf-input @error("$group.$i.full_name") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_name"
                   name="{{ $group }}[{{ $i }}][full_name]"
                   value="{{ old("$group.$i.full_name") }}"
                   placeholder="{{ $cfg['name_placeholder'] }}"
                   required>
            @if (!empty($cfg['domestic_name_hint']))
                <div class="form-text small text-muted" style="font-size: 0.74rem;">{{ $cfg['domestic_name_hint'] }}</div>
            @endif
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_doc">
                {{ $cfg['domestic_doc_label'] }}
            </label>
            <input type="text"
                   class="form-control bf-input @error("$group.$i.document_number") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_doc"
                   name="{{ $group }}[{{ $i }}][document_number]"
                   value="{{ old("$group.$i.document_number") }}"
                   placeholder="{{ $cfg['domestic_doc_placeholder'] }}">
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_dob">
                Ngày sinh
            </label>
            <input type="date"
                   class="form-control bf-input @error("$group.$i.date_of_birth") is-invalid @enderror"
                   id="{{ $group }}_{{ $i }}_dob"
                   name="{{ $group }}[{{ $i }}][date_of_birth]"
                   value="{{ old("$group.$i.date_of_birth") }}">
        </div>
    @endif

    {{-- điểm mở rộng hành lý (Luân) --}}
    @includeIf('booking.partials.passenger-baggage', ['group' => $group, 'i' => $i])

    @if ($group === 'infants')
        <div class="col-12">
            <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_companion">
                Đi cùng người lớn <span class="text-danger">*</span>
            </label>
            <select class="form-select bf-input @error("$group.$i.companion_adult_index") is-invalid @enderror"
                    id="{{ $group }}_{{ $i }}_companion"
                    name="{{ $group }}[{{ $i }}][companion_adult_index]"
                    required>
                <option value="">-- Chọn người lớn bảo hộ --</option>
                @for ($a = 0; $a < $pending['adults']; $a++)
                    <option value="{{ $a }}" {{ (string)old("$group.$i.companion_adult_index") === (string)$a ? 'selected' : '' }}>
                        Người lớn #{{ $a + 1 }}
                    </option>
                @endfor
            </select>
            <div class="form-text small text-muted" style="font-size: 0.74rem;">Mỗi em bé bắt buộc có một người lớn đi kèm bảo hộ.</div>
        </div>
    @endif
</div>
