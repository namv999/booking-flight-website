{{-- Ô chọn gói hành lý mua thêm cho MỘT hành khách. Em bé không có ô này. --}}
{{-- Biến: $group (adults|children|infants), $i, $baggageAddons, $freeBaggageKg, $pending --}}
@if ($group !== 'infants')
    @php
        $fieldKey = "$group.$i.baggage_addon_id";
        $isMultiLeg = count($pending['legs'] ?? []) > 1;
    @endphp

    <div class="col-12 col-md-6">
        <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_baggage">
            Hành lý mua thêm
        </label>
        <select class="form-select bf-input @error($fieldKey) is-invalid @enderror"
                id="{{ $group }}_{{ $i }}_baggage"
                name="{{ $group }}[{{ $i }}][baggage_addon_id]">
            <option value="">Không mua thêm</option>
            @foreach ($baggageAddons as $addon)
                <option value="{{ $addon->id }}" {{ (string) old($fieldKey) === (string) $addon->id ? 'selected' : '' }}>
                    {{ $addon->name }} ({{ $addon->weight_kg }}kg) - {{ number_format($addon->price, 0, ',', '.') }} đ{{ $isMultiLeg ? ' / chặng' : '' }}
                </option>
            @endforeach
        </select>
        @error($fieldKey)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text small text-muted" style="font-size: 0.74rem;">
            @if ($freeBaggageKg)
                Hạng vé đã gồm {{ $freeBaggageKg }}kg ký gửi miễn phí.
            @endif
            @if ($isMultiLeg)
                Giá gói tính cho mỗi chặng, áp dụng cho mọi chặng của hành khách này.
            @endif
        </div>
    </div>
@endif
