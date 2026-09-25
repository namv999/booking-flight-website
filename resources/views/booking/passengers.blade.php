@extends('layouts.app')

@section('title', 'Thông tin hành khách - ' . $flight->departureAirport->iata_code . ' đến ' . $flight->arrivalAirport->iata_code . ' - Jet Charter')

@section('styles')
    @vite(['resources/css/flights.css'])
@endsection

@section('content')
<div class="bf-results-page">
    {{-- 1. THANH TIẾN TRÌNH 5 BƯỚC (BOOKING PROGRESS WIZARD) --}}
    <div class="bf-step-wizard">
        <div class="container">
            <ol class="bf-steps">
                <li class="bf-step-item completed">
                    <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                    <span class="d-none d-sm-inline">Tìm kiếm</span>
                </li>
                <li class="bf-step-divider completed"></li>
                <li class="bf-step-item completed">
                    <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                    <span class="d-none d-sm-inline">Chọn chuyến bay</span>
                </li>
                <li class="bf-step-divider completed"></li>
                <li class="bf-step-item active" aria-current="step">
                    <span class="bf-step-circle">3</span>
                    <span>Thông tin khách</span>
                </li>
                <li class="bf-step-divider"></li>
                <li class="bf-step-item">
                    <span class="bf-step-circle">4</span>
                    <span class="d-none d-sm-inline">Thanh toán</span>
                </li>
                <li class="bf-step-divider"></li>
                <li class="bf-step-item">
                    <span class="bf-step-circle">5</span>
                    <span class="d-none d-sm-inline">Hoàn tất</span>
                </li>
            </ol>
        </div>
    </div>

    <div class="container">
        {{-- THÔNG BÁO FLASH / VALIDATION ERRORS --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0 fs-5"></i>
                <div>{{ session('status') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>Vui lòng kiểm tra và hoàn thiện các trường sau:</div>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        <div class="row g-4">
            {{-- CỘT TRÁI: FORM NHẬP THÔNG TIN HÀNH KHÁCH --}}
            <main class="col-lg-8 col-md-7">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h1 class="h4 fw-bold text-navy mb-1" style="color: var(--bf-navy);">Thông tin hành khách</h1>
                        <p class="text-muted small mb-0">Vui lòng điền thông tin chính xác theo CCCD hoặc Hộ chiếu sử dụng khi lên máy bay.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('booking.passengers.store') }}" id="bf-passenger-form">
                    @csrf

                    {{-- 1. DANH SÁCH NGƯỜI LỚN --}}
                    <div class="bf-passenger-group-heading">
                        <i class="bi bi-people-fill text-primary" aria-hidden="true"></i>
                        <span>Người lớn ({{ $pending['adults'] }} hành khách)</span>
                    </div>

                    @for ($i = 0; $i < $pending['adults']; $i++)
                        <div class="bf-passenger-card">
                            <div class="bf-passenger-header">
                                <h2 class="bf-passenger-title">
                                    <span class="bf-passenger-icon-circle" aria-hidden="true">
                                        <i class="bi bi-person-fill"></i>
                                    </span>
                                    <span>Hành khách {{ $i + 1 }}: Người lớn</span>
                                </h2>
                                <span class="bf-passenger-badge bf-passenger-badge--adult">Từ 12 tuổi</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-bold small text-navy" for="adults_{{ $i }}_name">
                                        Họ và tên <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control bf-input @error("adults.$i.full_name") is-invalid @enderror"
                                           id="adults_{{ $i }}_name"
                                           name="adults[{{ $i }}][full_name]"
                                           value="{{ old("adults.$i.full_name") }}"
                                           placeholder="Ví dụ: NGUYEN VAN A"
                                           required>
                                    <div class="form-text small text-muted" style="font-size: 0.74rem;">Như trên giấy tờ tùy thân (CCCD/Hộ chiếu)</div>
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-bold small text-navy" for="adults_{{ $i }}_doc">
                                        Số CCCD / Hộ chiếu
                                    </label>
                                    <input type="text"
                                           class="form-control bf-input @error("adults.$i.document_number") is-invalid @enderror"
                                           id="adults_{{ $i }}_doc"
                                           name="adults[{{ $i }}][document_number]"
                                           value="{{ old("adults.$i.document_number") }}"
                                           placeholder="001234567890">
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-bold small text-navy" for="adults_{{ $i }}_dob">
                                        Ngày sinh
                                    </label>
                                    <input type="date"
                                           class="form-control bf-input @error("adults.$i.date_of_birth") is-invalid @enderror"
                                           id="adults_{{ $i }}_dob"
                                           name="adults[{{ $i }}][date_of_birth]"
                                           value="{{ old("adults.$i.date_of_birth") }}">
                                </div>
                            </div>
                        </div>
                    @endfor

                    {{-- 2. DANH SÁCH TRẺ EM (NẾU CÓ) --}}
                    @if (isset($pending['children']) && $pending['children'] > 0)
                        <div class="bf-passenger-group-heading">
                            <i class="bi bi-person text-success" aria-hidden="true"></i>
                            <span>Trẻ em ({{ $pending['children'] }} hành khách)</span>
                        </div>

                        @for ($i = 0; $i < $pending['children']; $i++)
                            <div class="bf-passenger-card">
                                <div class="bf-passenger-header">
                                    <h2 class="bf-passenger-title">
                                        <span class="bf-passenger-icon-circle" style="background:#f0fbf7; color:#16805f;" aria-hidden="true">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <span>Trẻ em {{ $i + 1 }}</span>
                                    </h2>
                                    <span class="bf-passenger-badge bf-passenger-badge--child">Từ 2 đến dưới 12 tuổi</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold small text-navy" for="children_{{ $i }}_name">
                                            Họ và tên <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               class="form-control bf-input @error("children.$i.full_name") is-invalid @enderror"
                                               id="children_{{ $i }}_name"
                                               name="children[{{ $i }}][full_name]"
                                               value="{{ old("children.$i.full_name") }}"
                                               placeholder="Ví dụ: NGUYEN VAN B"
                                               required>
                                        <div class="form-text small text-muted" style="font-size: 0.74rem;">Như trên giấy khai sinh hoặc hộ chiếu</div>
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label class="form-label fw-bold small text-navy" for="children_{{ $i }}_doc">
                                            Số giấy tờ (nếu có)
                                        </label>
                                        <input type="text"
                                               class="form-control bf-input @error("children.$i.document_number") is-invalid @enderror"
                                               id="children_{{ $i }}_doc"
                                               name="children[{{ $i }}][document_number]"
                                               value="{{ old("children.$i.document_number") }}"
                                               placeholder="Số định danh / GKS">
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label class="form-label fw-bold small text-navy" for="children_{{ $i }}_dob">
                                            Ngày sinh
                                        </label>
                                        <input type="date"
                                               class="form-control bf-input @error("children.$i.date_of_birth") is-invalid @enderror"
                                               id="children_{{ $i }}_dob"
                                               name="children[{{ $i }}][date_of_birth]"
                                               value="{{ old("children.$i.date_of_birth") }}">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif

                    {{-- 3. DANH SÁCH EM BÉ (NẾU CÓ) --}}
                    @if (isset($pending['infants']) && $pending['infants'] > 0)
                        <div class="bf-passenger-group-heading">
                            <i class="bi bi-emoji-smile text-warning" aria-hidden="true"></i>
                            <span>Em bé ({{ $pending['infants'] }} hành khách)</span>
                        </div>

                        @for ($i = 0; $i < $pending['infants']; $i++)
                            <div class="bf-passenger-card">
                                <div class="bf-passenger-header">
                                    <h2 class="bf-passenger-title">
                                        <span class="bf-passenger-icon-circle" style="background:#fff8eb; color:#b56a12;" aria-hidden="true">
                                            <i class="bi bi-emoji-smile"></i>
                                        </span>
                                        <span>Em bé {{ $i + 1 }}</span>
                                    </h2>
                                    <span class="bf-passenger-badge bf-passenger-badge--infant">Dưới 2 tuổi</span>
                                </div>

                                <div class="bf-infant-notice">
                                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                                    <span>Em bé ngồi cùng người lớn đi kèm và không chiếm ghế riêng. Giá vé bằng 10% giá vé người lớn.</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold small text-navy" for="infants_{{ $i }}_name">
                                            Họ và tên <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               class="form-control bf-input @error("infants.$i.full_name") is-invalid @enderror"
                                               id="infants_{{ $i }}_name"
                                               name="infants[{{ $i }}][full_name]"
                                               value="{{ old("infants.$i.full_name") }}"
                                               placeholder="Ví dụ: NGUYEN THI C"
                                               required>
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label class="form-label fw-bold small text-navy" for="infants_{{ $i }}_doc">
                                            Số giấy khai sinh
                                        </label>
                                        <input type="text"
                                               class="form-control bf-input @error("infants.$i.document_number") is-invalid @enderror"
                                               id="infants_{{ $i }}_doc"
                                               name="infants[{{ $i }}][document_number]"
                                               value="{{ old("infants.$i.document_number") }}"
                                               placeholder="GKS">
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label class="form-label fw-bold small text-navy" for="infants_{{ $i }}_dob">
                                            Ngày sinh
                                        </label>
                                        <input type="date"
                                               class="form-control bf-input @error("infants.$i.date_of_birth") is-invalid @enderror"
                                               id="infants_{{ $i }}_dob"
                                               name="infants[{{ $i }}][date_of_birth]"
                                               value="{{ old("infants.$i.date_of_birth") }}">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-navy" for="infants_{{ $i }}_companion">
                                            Đi cùng người lớn <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select bf-input @error("infants.$i.companion_adult_index") is-invalid @enderror"
                                                id="infants_{{ $i }}_companion"
                                                name="infants[{{ $i }}][companion_adult_index]"
                                                required>
                                            <option value="">-- Chọn người lớn bảo hộ --</option>
                                            @for ($a = 0; $a < $pending['adults']; $a++)
                                                <option value="{{ $a }}" {{ (string)old("infants.$i.companion_adult_index") === (string)$a ? 'selected' : '' }}>
                                                    Người lớn #{{ $a + 1 }}
                                                </option>
                                            @endfor
                                        </select>
                                        <div class="form-text small text-muted" style="font-size: 0.74rem;">Mỗi em bé bắt buộc có một người lớn đi kèm bảo hộ.</div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif

                    {{-- NÚT SUBMIT FORM --}}
                    <div class="mt-4 mb-5">
                        <button type="submit" class="bf-btn-submit-passengers" id="bf-btn-submit-passengers">
                            <span>Tiếp tục thanh toán</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                        <div class="text-center mt-2">
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-shield-check text-success me-1"></i> Bằng việc tiếp tục, bạn đồng ý với Điều khoản dịch vụ và Chính sách bảo mật của Jet Charter.
                            </small>
                        </div>
                    </div>
                </form>
            </main>

            {{-- CỘT PHẢI: SIDEBAR TÓM TẮT CHUYẾN BAY (BOOKING SIDEBAR) --}}
            <aside class="col-lg-4 col-md-5">
                @php
                    $depTime = \Carbon\Carbon::parse($flight->departure_time);
                    $arrTime = \Carbon\Carbon::parse($flight->arrival_time);
                    $diffMinutes = $depTime->diffInMinutes($arrTime);
                    $hours = intdiv($diffMinutes, 60);
                    $mins = $diffMinutes % 60;
                    $durationText = ($hours > 0 ? "{$hours}h " : "") . ($mins > 0 ? "{$mins}m" : "00m");
                    $airlineName = $flight->aircraft->airline->name ?? 'Hãng hàng không';
                    $aircraftModel = $flight->aircraft->model ?? 'Aircraft';
                    $seatsNeeded = $pending['adults'] + ($pending['children'] ?? 0);
                @endphp

                <div class="bf-booking-sidebar">
                    <h3 class="bf-booking-sidebar__title">
                        <i class="bi bi-ticket-detailed-fill text-primary" aria-hidden="true"></i>
                        <span>Tóm tắt hành trình</span>
                    </h3>

                    <div class="bf-sidebar-route">
                        <div class="bf-sidebar-route-item">
                            <strong>{{ $flight->departureAirport->iata_code }}</strong>
                            <small>{{ $flight->departureAirport->city }}</small>
                        </div>
                        <i class="bi bi-arrow-right bf-sidebar-arrow" aria-hidden="true"></i>
                        <div class="bf-sidebar-route-item text-end">
                            <strong>{{ $flight->arrivalAirport->iata_code }}</strong>
                            <small>{{ $flight->arrivalAirport->city }}</small>
                        </div>
                    </div>

                    <div class="bf-sidebar-airline">
                        <i class="bi bi-airplane" aria-hidden="true"></i>
                        <div>
                            <div class="fw-bold text-navy">{{ $airlineName }}</div>
                            <div class="small text-muted">{{ $aircraftModel }} &bull; Chuyến #FL-{{ str_pad($flight->id, 4, '0', STR_PAD_LEFT) }}</div>
                        </div>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Cất cánh:</span>
                        <strong>{{ $depTime->format('H:i, d/m/Y') }}</strong>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Hạ cánh:</span>
                        <strong>{{ $arrTime->format('H:i, d/m/Y') }}</strong>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Thời gian bay:</span>
                        <span>{{ $durationText }} &bull; Bay thẳng</span>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Số lượng khách:</span>
                        <strong>
                            {{ $pending['adults'] }} Người lớn
                            @if (isset($pending['children']) && $pending['children'] > 0), {{ $pending['children'] }} Trẻ em @endif
                            @if (isset($pending['infants']) && $pending['infants'] > 0), {{ $pending['infants'] }} Em bé @endif
                        </strong>
                    </div>

                    <div class="bf-sidebar-meta-row">
                        <span>Số ghế cần đặt:</span>
                        <strong class="text-primary">{{ $seatsNeeded }} ghế riêng</strong>
                    </div>

                    <div class="bf-sidebar-security-badge">
                        <i class="bi bi-shield-check text-success" aria-hidden="true"></i>
                        <span>Ghế sẽ được hệ thống giữ tự động ngay sau khi bạn xác nhận thông tin hành khách.</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('bf-passenger-form');
        const submitBtn = document.getElementById('bf-btn-submit-passengers');

        if (form && submitBtn) {
            form.addEventListener('submit', function (e) {
                if (!form.checkValidity()) {
                    return;
                }

                // Ngăn chặn double-click / double-submit trong lúc Backend thực hiện Atomic Hold Transaction
                submitBtn.style.pointerEvents = 'none';
                submitBtn.style.opacity = '0.85';
                submitBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span>Đang xử lý & giữ chỗ...</span>
                `;

                setTimeout(function () {
                    submitBtn.disabled = true;
                }, 0);
            });
        }
    });
</script>
@endsection