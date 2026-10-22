{{-- resources/views/booking/seats.blade.php --}}
{{-- BƯỚC "CHỌN GHẾ": chọn ghế riêng cho từng chặng. Hợp đồng DOM/JSON xem UI-GUIDE-SeatMap.md --}}
{{-- Biến: $flights, $passengers, $infants, $fareClass, $mapUrls, $oldSeats --}}

@extends('layouts.app')

@section('title', 'Chọn ghế - Jet Charter')

@section('styles')
    @vite(['resources/css/flights.css', 'resources/css/seat-map.css'])
@endsection

@section('content')
@php
    // Thứ tự chặng = thứ tự $flights (đã sắp theo departure_time); vị trí trong danh sách là chỉ số chặng `leg`
    $flights = $flights->values();
    $legCount = $flights->count();
    $firstFlight = $flights->first();
    $lastFlight = $flights->last();
    $isRoundTrip = $legCount === 2 && $firstFlight && $lastFlight
        && $firstFlight->departure_airport_id === $lastFlight->arrival_airport_id
        && $firstFlight->arrival_airport_id === $lastFlight->departure_airport_id;

    // Giờ bay lưu UTC trong DB; hiển thị theo múi giờ của sân bay tương ứng (múi giờ lạ thì dùng UTC)
    $zones = \DateTimeZone::listIdentifiers();
    $localTime = function ($flight, string $field, $airport) use ($zones) {
        $raw = $flight->getRawOriginal($field);
        if (! $raw) {
            return null;
        }
        $tz = $airport?->timezone;
        if (! $tz || ! in_array($tz, $zones, true)) {
            $tz = 'UTC';
        }

        return \Carbon\Carbon::parse($raw, 'UTC')->setTimezone($tz);
    };

    $legs = [];
    foreach ($flights as $i => $f) {
        $dep = $f->departureAirport;
        $arr = $f->arrivalAirport;
        $route = ($dep?->iata_code ?? '?').' → '.($arr?->iata_code ?? '?');
        $label = $legCount > 1
            ? ($isRoundTrip ? ($i === 0 ? 'Chuyến đi' : 'Chuyến về') : 'Chặng '.($i + 1).'/'.$legCount).': '.$route
            : $route;
        $legs[$i] = [
            'flight' => $f,
            'dep' => $dep,
            'arr' => $arr,
            'depTime' => $localTime($f, 'departure_time', $dep),
            'arrTime' => $localTime($f, 'arrival_time', $arr),
            'route' => $route,
            'label' => $label,
        ];
    }
@endphp

<div class="bf-results-page bf-seat-page">
    {{-- 1. THANH TIẾN TRÌNH 6 BƯỚC (BOOKING PROGRESS WIZARD) --}}
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
                <li class="bf-step-item completed">
                    <span class="bf-step-circle"><i class="bi bi-check-lg"></i></span>
                    <span class="d-none d-sm-inline">Thông tin khách</span>
                </li>
                <li class="bf-step-divider completed"></li>
                <li class="bf-step-item active" aria-current="step">
                    <span class="bf-step-circle">4</span>
                    <span>Chọn ghế</span>
                </li>
                <li class="bf-step-divider"></li>
                <li class="bf-step-item">
                    <span class="bf-step-circle">5</span>
                    <span class="d-none d-sm-inline">Thanh toán</span>
                </li>
                <li class="bf-step-divider"></li>
                <li class="bf-step-item">
                    <span class="bf-step-circle">6</span>
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
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>Vui lòng kiểm tra lại lựa chọn ghế:</div>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        <div class="row g-4">
            {{-- CỘT TRÁI: SƠ ĐỒ GHẾ --}}
            <main class="col-lg-8">
                <div class="mb-3">
                    <h1 class="h4 fw-bold mb-1" style="color: var(--bf-navy);">Chọn ghế</h1>
                    <p class="text-muted small mb-2">
                        Hạng vé <strong>{{ $fareClass->name }}</strong>. Xếp ghế tự động là mặc định và miễn phí; chọn ghế riêng sẽ có phí chọn ghế.
                    </p>
                    <a href="{{ route('booking.passengers.form') }}" class="small text-decoration-none">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Quay lại thông tin khách
                    </a>
                </div>

                <div id="seat-map-app"
                     data-map-urls='@json($mapUrls)'
                     data-old-seats='@json($oldSeats)'
                     data-passengers='@json($passengers)'
                     data-home-url="{{ route('home') }}">

                    {{-- Mỗi chặng một tab; chuyển qua lại giữ nguyên lựa chọn --}}
                    <div class="bf-seat-tabs" role="tablist" aria-label="Các chặng bay">
                        @foreach ($legs as $i => $leg)
                            <button type="button"
                                    class="bf-seat-tab {{ $i === 0 ? 'is-active' : '' }}"
                                    role="tab"
                                    id="seat-tab-{{ $i }}"
                                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                                    aria-controls="seat-panel"
                                    data-leg-tab="{{ $i }}"
                                    data-label="{{ $leg['label'] }}">
                                <span class="bf-seat-tab__title">{{ $leg['label'] }}</span>
                                @if ($leg['depTime'])
                                    <span class="bf-seat-tab__time">{{ $leg['depTime']->format('d/m H:i') }}</span>
                                @endif
                                <span class="bf-seat-tab__count" data-leg-count="{{ $i }}"></span>
                            </button>
                        @endforeach
                    </div>

                    <div id="seat-panel" class="bf-seat-panel" role="tabpanel">
                        {{-- Thông tin chuyến của chặng đang mở --}}
                        @foreach ($legs as $i => $leg)
                            <div class="bf-seat-leginfo" data-leg-info="{{ $i }}" @if ($i !== 0) hidden @endif>
                                <div class="bf-seat-leginfo__route">
                                    <div>
                                        <strong>{{ $leg['dep']?->iata_code }}</strong>
                                        <small class="text-muted">{{ $leg['dep']?->city }}</small>
                                        @if ($leg['depTime'])
                                            <div class="small">{{ $leg['depTime']->format('H:i, d/m/Y') }} <span class="text-muted">(UTC{{ $leg['depTime']->format('P') }})</span></div>
                                        @endif
                                    </div>
                                    <i class="bi bi-arrow-right text-muted" aria-hidden="true"></i>
                                    <div class="text-end">
                                        <strong>{{ $leg['arr']?->iata_code }}</strong>
                                        <small class="text-muted">{{ $leg['arr']?->city }}</small>
                                        @if ($leg['arrTime'])
                                            <div class="small">{{ $leg['arrTime']->format('H:i, d/m/Y') }} <span class="text-muted">(UTC{{ $leg['arrTime']->format('P') }})</span></div>
                                        @endif
                                    </div>
                                </div>
                                <div class="small text-muted mt-1">
                                    {{ $leg['flight']->aircraft?->airline?->name ?? 'Hãng hàng không' }}
                                    &bull; {{ $leg['flight']->aircraft?->model ?? 'Máy bay' }}
                                    &bull; Chuyến #FL-{{ str_pad($leg['flight']->id, 4, '0', STR_PAD_LEFT) }}
                                </div>
                            </div>
                        @endforeach

                        @if (! empty($infants))
                            <div class="alert alert-info d-flex align-items-start gap-2 py-2 small mb-3" role="note">
                                <i class="bi bi-info-circle-fill flex-shrink-0" aria-hidden="true"></i>
                                <div>
                                    Em bé ngồi cùng người lớn, không cần chọn ghế.
                                    <span class="text-muted">({{ implode(', ', $infants) }})</span>
                                </div>
                            </div>
                        @endif

                        {{-- Hành khách đang chọn ghế (JS sinh chip) --}}
                        <div class="bf-seat-subtitle" id="seat-chips-title">Hành khách (bấm để chọn người nhận ghế)</div>
                        <div id="seat-chips" class="bf-seat-chips" role="group" aria-labelledby="seat-chips-title"></div>

                        <div id="seat-notice" class="bf-seat-notice" role="status" aria-live="polite"></div>
                        <div id="seat-warnings" class="bf-seat-warnings" role="alert"></div>

                        {{-- Chú giải --}}
                        <div class="bf-seat-legend" aria-label="Chú giải">
                            <span class="bf-seat-legend__item"><span class="bf-seat-swatch bf-seat-swatch--available" aria-hidden="true"></span>Trống</span>
                            <span class="bf-seat-legend__item"><span class="bf-seat-swatch bf-seat-swatch--selected" aria-hidden="true"></span>Đang chọn</span>
                            <span class="bf-seat-legend__item"><span class="bf-seat-swatch bf-seat-swatch--booked" aria-hidden="true"></span>Đã đặt</span>
                            <span class="bf-seat-legend__item"><span class="bf-seat-swatch bf-seat-swatch--held" aria-hidden="true"></span>Đang được giữ</span>
                            <button type="button" id="seat-reload" class="btn btn-sm btn-outline-secondary ms-auto">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Làm mới sơ đồ
                            </button>
                        </div>
                        <div id="seat-fee-note" class="bf-seat-fee-note"></div>

                        {{-- Lưới ghế (JS vẽ từ JSON) --}}
                        <div class="bf-seat-stage">
                            <div id="seat-grid" class="bf-seat-grid"></div>
                        </div>
                    </div>
                </div>

                {{-- Tóm tắt từng chặng --}}
                <section class="bf-seat-summary" aria-labelledby="seat-summary-title">
                    <h2 class="bf-seat-summary__title" id="seat-summary-title">Tóm tắt lựa chọn ghế</h2>
                    <div id="seat-summary-list"></div>
                </section>
            </main>

            {{-- CỘT PHẢI: XÁC NHẬN (thanh cố định phía dưới trên điện thoại) --}}
            <aside class="col-lg-4">
                <form id="seat-form" class="bf-seat-actions" method="POST" action="{{ route('booking.seats.store') }}">
                    @csrf
                    <div id="seat-inputs"></div> {{-- JS sinh các input hidden ở đây --}}

                    <div class="bf-seat-actions__info">
                        <div class="bf-seat-actions__fee">
                            Phí chọn ghế tạm tính: <strong id="seat-fee-total">0 đ</strong>
                        </div>
                        <div class="bf-seat-actions__free small text-muted">Ghế xếp tự động: miễn phí</div>
                        <div class="bf-seat-actions__hint small text-muted d-none d-lg-block">
                            Ghế chỉ được giữ khi bạn bấm Xác nhận đặt vé. Phí chính thức do hệ thống tính lại.
                        </div>
                    </div>

                    <div class="bf-seat-actions__buttons">
                        <button type="submit" id="seat-confirm" class="bf-btn-submit-passengers">Xác nhận đặt vé</button>
                        <button type="button" id="seat-auto" class="btn btn-outline-secondary">Xếp ghế tự động</button>
                    </div>
                </form>
            </aside>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/seat-map.js'])
@endsection
