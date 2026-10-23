{{-- Nạp script autofill đúng 1 lần cho cả trang (partial này được include cho từng hành khách) --}}
@once
    @include('booking.partials.passenger-autofill-scripts')
@endonce

<div class="col-12">
    <label class="form-label fw-bold small text-navy" for="{{ $group }}_{{ $i }}_autofill">
        Chọn từ hồ sơ đã lưu
    </label>
    {{-- Cố ý KHÔNG có thuộc tính name: ô này chỉ là tiện ích, không được gửi lên server --}}
    <select class="form-select bf-input"
            id="{{ $group }}_{{ $i }}_autofill"
            data-autofill-group="{{ $group }}"
            data-autofill-index="{{ $i }}"
            data-options-url="{{ route('saved-passengers.options') }}">
        <option value="">-- Nhập tay --</option>
    </select>
</div>