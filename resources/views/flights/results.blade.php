@extends('layouts.app')

@section('title', 'Kết quả tìm kiếm chuyến bay: ' . $departureAirport->city . ' (' . $departureAirport->iata_code . ') đến ' . $arrivalAirport->city . ' (' . $arrivalAirport->iata_code . ') - Jet Charter')

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
                <li class="bf-step-item active" aria-current="step">
                    <span class="bf-step-circle">2</span>
                    <span>Chọn chuyến bay</span>
                </li>
                <li class="bf-step-divider"></li>
                <li class="bf-step-item">
                    <span class="bf-step-circle">3</span>
                    <span class="d-none d-sm-inline">Thông tin khách</span>
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
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>Vui lòng kiểm tra lại:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
        @endif

        {{-- 2. TÓM TẮT ĐIỀU KIỆN TÌM KIẾM (SEARCH SUMMARY BAR) --}}
        @php
        $depDate = \Carbon\Carbon::parse($departureDate);
        $dayOfWeekNames = [
        0 => 'Chủ Nhật',
        1 => 'Thứ Hai',
        2 => 'Thứ Ba',
        3 => 'Thứ Tư',
        4 => 'Thứ Năm',
        5 => 'Thứ Sáu',
        6 => 'Thứ Bảy',
        ];
        $dayName = $dayOfWeekNames[$depDate->dayOfWeek] ?? '';
        @endphp
        <section class="bf-summary-bar" aria-label="Thông tin tìm kiếm">
            @if ($totalLegs > 1)
            <div class="mb-2">
                <span class="badge bg-primary">Chặng {{ $legIndex + 1 }}/{{ $totalLegs }}</span>
                <span class="fw-semibold small ms-1">{{ $tripType === 'multi-city' ? 'Chặng '.($legIndex + 1) : ($legIndex === 0 ? 'Chuyến đi' : 'Chuyến về') }}</span>
            </div>
            @endif

            <div class="bf-summary-route">
                <div>
                    <span class="bf-summary-city">{{ $departureAirport->city }}</span>
                    <span class="bf-summary-code">{{ $departureAirport->iata_code }}</span>
                </div>
                <i class="bi bi-arrow-right bf-summary-arrow" aria-hidden="true"></i>
                <div>
                    <span class="bf-summary-city">{{ $arrivalAirport->city }}</span>
                    <span class="bf-summary-code">{{ $arrivalAirport->iata_code }}</span>
                </div>
            </div>

            <div class="bf-summary-details">
                <div class="bf-summary-meta-item">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <span>{{ $dayName }}, {{ $depDate->format('d/m/Y') }}</span>
                </div>
                <div class="bf-summary-meta-item">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span>
                        {{ $adults }} Người lớn
                        @if ($children > 0), {{ $children }} Trẻ em @endif
                        @if ($infants > 0), {{ $infants }} Em bé @endif
                    </span>
                </div>
                <div class="bf-summary-meta-item">
                    <i class="bi bi-award" aria-hidden="true"></i>
                    <span class="fw-semibold">Hạng {{ $fareClass->name }}</span>
                </div>
            </div>

            <div>
                <a href="{{ url('/') }}#search-box" class="bf-btn-modify">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Thay đổi tìm kiếm
                </a>
            </div>
        </section>

        @if ($chosenFlights->isNotEmpty())
        <div class="alert alert-light border mb-4">
            <div class="fw-bold small text-muted text-uppercase mb-2">Chuyến bạn đã chọn</div>
            @foreach ($chosenFlights as $i => $cf)
            @php
            $cfDep = \Carbon\Carbon::parse($cf->getRawOriginal('departure_time'), 'UTC')
            ->setTimezone($cf->departureAirport->timezone);
            @endphp
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 {{ ! $loop->last ? 'mb-2' : '' }}">
                <div>
                    <span class="badge bg-primary text-white me-1">Chặng {{ $i + 1 }}</span>
                    <strong>{{ $cf->departureAirport->iata_code }} → {{ $cf->arrivalAirport->iata_code }}</strong>
                    <span class="text-muted small ms-2">
                        {{ $cfDep->format('H:i d/m/Y') }} · {{ $cf->aircraft->airline->name ?? '' }}
                    </span>
                </div>
            </div>
            @endforeach
            <a href="{{ $backUrl }}" class="small text-decoration-none">Chọn lại chuyến trước</a>
        </div>
        @endif

        {{-- 3. KHU VỰC NỘI DUNG CHÍNH (SIDEBAR FILTER + FLIGHT LIST) --}}
        <div class="row g-4">
            {{-- SIDEBAR FILTER (BỘ LỌC GIAO DIỆN TRỰC QUAN) --}}
            <aside class="col-lg-3 col-md-4">
                <div class="offcanvas-md offcanvas-start bf-filter-offcanvas" tabindex="-1" id="bfFilterOffcanvas" aria-labelledby="bfFilterOffcanvasLabel">
                    <div class="offcanvas-header d-md-none border-bottom p-3">
                        <h5 class="offcanvas-title fw-bold" id="bfFilterOffcanvasLabel">
                            <i class="bi bi-funnel-fill text-primary me-2"></i>Bộ lọc chuyến bay
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#bfFilterOffcanvas" aria-label="Đóng"></button>
                    </div>
                    <div class="offcanvas-body p-0">
                        <form id="bf-filter-form" method="GET" action="{{ $filterAction }}" class="w-100">
                            <div class="bf-filter-card">
                                <div class="bf-filter-card__header">
                                    <h2 class="bf-filter-card__title">
                                        <i class="bi bi-funnel-fill text-primary" aria-hidden="true"></i> Bộ lọc
                                    </h2>
                                    @if ($hasActiveFilters || $filters['sort'] !== 'price_asc')
                                    <a href="{{ $filterAction }}" class="bf-filter-card__reset">Đặt lại</a>
                                    @endif
                                </div>

                                {{-- Lọc Hãng hàng không (Searchable Multi-Select Dropdown Hiện Đại) --}}
                                <div class="bf-filter-section bf-filter-section--airline">
                                    <div class="bf-filter-label">
                                        <span class="d-flex align-items-center gap-1">
                                            <span>Hãng hàng không</span>
                                            @if (!empty($filters['airlines']))
                                            <span class="badge bg-primary text-white rounded-pill px-2 py-0" style="font-size: 0.7rem;">{{ count($filters['airlines']) }}</span>
                                            @endif
                                        </span>
                                        <i class="bi bi-airplane" aria-hidden="true"></i>
                                    </div>

                                    <div class="bf-airline-dropdown" id="bf-airline-dropdown">
                                        {{-- Trigger button --}}
                                        <button type="button"
                                            class="bf-airline-trigger {{ !empty($filters['airlines']) ? 'has-selection' : '' }}"
                                            id="bf-airline-trigger"
                                            aria-expanded="false"
                                            aria-controls="bf-airline-menu"
                                            aria-label="Chọn hãng hàng không">
                                            <span class="bf-airline-trigger__text" id="bf-airline-trigger-text">
                                                @if (empty($filters['airlines']))
                                                <span><i class="bi bi-airplane-engines text-primary me-2"></i><strong class="text-dark">Tất cả hãng bay</strong> <span class="text-secondary fw-semibold small">({{ $facets['airlines']->count() }})</span></span>
                                                @else
                                                <span class="bf-airline-trigger__selected">
                                                    <i class="bi bi-check2-circle text-primary me-1"></i>
                                                    <strong class="text-primary">Đã chọn {{ count($filters['airlines']) }} hãng</strong>
                                                </span>
                                                @endif
                                            </span>
                                            <i class="bi bi-chevron-down bf-airline-trigger__arrow" aria-hidden="true"></i>
                                        </button>

                                        {{-- Dropdown Menu Popover --}}
                                        <div class="bf-airline-menu" id="bf-airline-menu" role="dialog" aria-label="Bộ lọc hãng hàng không">
                                            {{-- Search Input with Keyboard Typing --}}
                                            <div class="bf-airline-search-bar">
                                                <i class="bi bi-search bf-airline-search-bar__icon" aria-hidden="true"></i>
                                                <input type="text"
                                                    class="bf-airline-search-input"
                                                    id="bf-airline-search-input"
                                                    placeholder="Gõ tìm hãng (VN, Vietjet, Bamboo...)"
                                                    autocomplete="off"
                                                    aria-label="Tìm kiếm hãng bay theo tên hoặc mã">
                                                <button type="button"
                                                    class="bf-airline-search-clear d-none"
                                                    id="bf-airline-search-clear"
                                                    aria-label="Xóa từ khóa tìm kiếm">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                </button>
                                            </div>

                                            {{-- Quick Actions: Select All / Clear --}}
                                            <div class="bf-airline-quick-row">
                                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none bf-quick-link" id="bf-airline-select-all">
                                                    <i class="bi bi-check-all me-1"></i>Chọn tất cả
                                                </button>
                                                <span class="text-secondary small">·</span>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none bf-quick-link text-secondary" id="bf-airline-clear-all">
                                                    Bỏ chọn
                                                </button>
                                                <span class="ms-auto text-dark small fw-bold" id="bf-airline-visible-count">{{ $facets['airlines']->count() }} hãng</span>
                                            </div>

                                            {{-- Scrollable List of Airline Items --}}
                                            <div class="bf-airline-list" id="bf-airline-list" role="listbox" aria-multiselectable="true">
                                                @forelse ($facets['airlines'] as $al)
                                                @php
                                                $isChecked = in_array($al['code'], $filters['airlines']);
                                                $codeUpper = strtoupper($al['code']);
                                                $badgeBg = '#eef3f7';
                                                $badgeColor = '#12365a';
                                                if ($codeUpper === 'VN') { $badgeBg = '#e0f2fe'; $badgeColor = '#0369a1'; }
                                                elseif ($codeUpper === 'VJ') { $badgeBg = '#fee2e2'; $badgeColor = '#b91c1c'; }
                                                elseif ($codeUpper === 'QH') { $badgeBg = '#dcfce7'; $badgeColor = '#15803d'; }
                                                elseif ($codeUpper === 'VU') { $badgeBg = '#fef3c7'; $badgeColor = '#b45309'; }
                                                @endphp
                                                <label class="bf-airline-option {{ $isChecked ? 'is-checked' : '' }}"
                                                    data-airline-code="{{ strtolower($al['code']) }}"
                                                    data-airline-name="{{ strtolower($al['name']) }}"
                                                    role="option"
                                                    aria-selected="{{ $isChecked ? 'true' : 'false' }}">
                                                    <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                                                        <input type="checkbox"
                                                            class="bf-airline-checkbox"
                                                            name="airlines[]"
                                                            value="{{ $al['code'] }}"
                                                            {{ $isChecked ? 'checked' : '' }}>
                                                        <span class="bf-airline-badge" style="background-color: {{ $badgeBg }}; color: {{ $badgeColor }};">
                                                            {{ $al['code'] }}
                                                        </span>
                                                        <span class="bf-airline-title text-truncate" title="{{ $al['name'] }}">{{ $al['name'] }}</span>
                                                    </div>
                                                    <span class="bf-airline-count-pill">{{ $al['count'] }}</span>
                                                </label>
                                                @empty
                                                <div class="text-secondary small fst-italic p-3 text-center">Không có dữ liệu hãng bay</div>
                                                @endforelse

                                                <div class="bf-airline-no-result d-none" id="bf-airline-no-result">
                                                    <i class="bi bi-search text-secondary fs-5 d-block mb-1"></i>
                                                    <span class="text-secondary fw-semibold">Không tìm thấy hãng bay phù hợp</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Selected Airlines Badges/Chips below trigger for direct removal --}}
                                    @if (!empty($filters['airlines']))
                                    <div class="bf-airline-chips mt-2 d-flex flex-wrap gap-1">
                                        @foreach ($filters['airlines'] as $alCode)
                                        @php
                                        $alMatch = $facets['airlines']->firstWhere('code', $alCode);
                                        $alName = $alMatch['name'] ?? $alCode;
                                        $remAirlines = array_values(array_diff($filters['airlines'], [$alCode]));
                                        $remQuery = request()->query();
                                        if (empty($remAirlines)) {
                                        unset($remQuery['airlines']);
                                        } else {
                                        $remQuery['airlines'] = $remAirlines;
                                        }
                                        unset($remQuery['page']);
                                        @endphp
                                        <a href="{{ $filterAction . (empty($remQuery) ? '' : '?' . http_build_query($remQuery)) }}"
                                            class="bf-airline-mini-chip"
                                            title="Bỏ chọn {{ $alName }}">
                                            <span>{{ $alName }}</span>
                                            <i class="bi bi-x" aria-hidden="true"></i>
                                        </a>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>

                                {{-- Lọc Khoảng giá --}}
                                <div class="bf-filter-section">
                                    <div class="bf-filter-label">
                                        <span>Khoảng giá (VND)</span>
                                        <i class="bi bi-tag" aria-hidden="true"></i>
                                    </div>
                                    <div class="bf-price-range">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label for="bf-price-min" class="form-label text-muted mb-1" style="font-size: 0.75rem;">Thấp nhất</label>
                                                <input type="number"
                                                    id="bf-price-min"
                                                    name="price_min"
                                                    class="form-control form-control-sm bf-price-input"
                                                    placeholder="{{ $facets['price_min'] !== null ? $facets['price_min'] : '' }}"
                                                    value="{{ $filters['price_min'] ?? '' }}"
                                                    min="0"
                                                    step="10000"
                                                    aria-label="Giá tối thiểu">
                                            </div>
                                            <div class="col-6">
                                                <label for="bf-price-max" class="form-label text-muted mb-1" style="font-size: 0.75rem;">Cao nhất</label>
                                                <input type="number"
                                                    id="bf-price-max"
                                                    name="price_max"
                                                    class="form-control form-control-sm bf-price-input"
                                                    placeholder="{{ $facets['price_max'] !== null ? $facets['price_max'] : '' }}"
                                                    value="{{ $filters['price_max'] ?? '' }}"
                                                    min="0"
                                                    step="10000"
                                                    aria-label="Giá tối đa">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100 rounded-pill">
                                            Áp dụng
                                        </button>
                                    </div>
                                </div>

                                {{-- Lọc Khung giờ cất cánh --}}
                                <div class="bf-filter-section">
                                    <div class="bf-filter-label">
                                        <span>Giờ cất cánh</span>
                                        <i class="bi bi-clock" aria-hidden="true"></i>
                                    </div>
                                    <div class="bf-time-slots">
                                        @foreach ($timeSlotOptions as $slotKey => $slot)
                                        <div class="bf-time-slot-item">
                                            <input type="checkbox"
                                                class="bf-time-slot-input"
                                                id="dep-slot-{{ $slotKey }}"
                                                name="dep_time[]"
                                                value="{{ $slotKey }}"
                                                {{ in_array($slotKey, $filters['dep_time']) ? 'checked' : '' }}>
                                            <label class="bf-time-slot-btn" for="dep-slot-{{ $slotKey }}">
                                                <strong>{{ $slot['label'] }}</strong>
                                                <span>{{ sprintf('%02d:00 - %02d:00', $slot['from'], $slot['to']) }}</span>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Lọc Khung giờ hạ cánh --}}
                                <div class="bf-filter-section">
                                    <div class="bf-filter-label">
                                        <span>Giờ hạ cánh</span>
                                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                                    </div>
                                    <div class="bf-time-slots">
                                        @foreach ($timeSlotOptions as $slotKey => $slot)
                                        <div class="bf-time-slot-item">
                                            <input type="checkbox"
                                                class="bf-time-slot-input"
                                                id="arr-slot-{{ $slotKey }}"
                                                name="arr_time[]"
                                                value="{{ $slotKey }}"
                                                {{ in_array($slotKey, $filters['arr_time']) ? 'checked' : '' }}>
                                            <label class="bf-time-slot-btn" for="arr-slot-{{ $slotKey }}">
                                                <strong>{{ $slot['label'] }}</strong>
                                                <span>{{ sprintf('%02d:00 - %02d:00', $slot['from'], $slot['to']) }}</span>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Ghi chú bộ lọc --}}
                                <div class="text-center pt-2">
                                    <small class="text-muted fst-italic" style="font-size: 0.73rem;">
                                        * Giá lọc theo giá thấp nhất của mỗi chuyến
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- DANH SÁCH CHUYẾN BAY (FLIGHT RESULTS LIST) --}}
            <main class="col-lg-9 col-md-8">
                {{-- Toolbar đếm số lượng + Sắp xếp nhanh --}}
                <div class="bf-results-toolbar d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                            class="btn btn-outline-primary btn-sm d-md-none rounded-pill px-3 py-1 d-inline-flex align-items-center gap-2"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#bfFilterOffcanvas"
                            aria-controls="bfFilterOffcanvas">
                            <i class="bi bi-funnel-fill"></i>
                            <span>Bộ lọc</span>
                            @php
                            $activeCount = count($filters['airlines']) + count($filters['dep_time']) + count($filters['arr_time']) + ($filters['price_min'] !== null ? 1 : 0) + ($filters['price_max'] !== null ? 1 : 0);
                            @endphp
                            @if ($activeCount > 0)
                            <span class="badge bg-primary text-white rounded-pill">{{ $activeCount }}</span>
                            @endif
                        </button>
                        <div class="bf-results-count">
                            Tìm thấy <strong>{{ $flights->total() }}</strong> chuyến bay phù hợp
                        </div>
                    </div>

                    <div class="bf-sort-group d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted small">Sắp xếp:</span>
                        @foreach ($sortOptions as $sortKey => $sortLabel)
                        <div class="bf-sort-item">
                            <input type="radio"
                                class="bf-sort-input"
                                name="sort"
                                id="sort-{{ $sortKey }}"
                                value="{{ $sortKey }}"
                                form="bf-filter-form"
                                {{ $filters['sort'] === $sortKey ? 'checked' : '' }}>
                            <label class="bf-sort-pill" for="sort-{{ $sortKey }}">
                                @if ($sortKey === 'price_asc')
                                <i class="bi bi-sort-numeric-down me-1"></i>
                                @elseif ($sortKey === 'price_desc')
                                <i class="bi bi-sort-numeric-down-alt me-1"></i>
                                @elseif ($sortKey === 'departure_asc' || $sortKey === 'arrival_asc')
                                <i class="bi bi-clock me-1"></i>
                                @elseif ($sortKey === 'departure_desc')
                                <i class="bi bi-clock-history me-1"></i>
                                @elseif ($sortKey === 'duration_asc')
                                <i class="bi bi-lightning-charge me-1"></i>
                                @endif
                                {{ $sortLabel }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Chips bộ lọc đang bật --}}
                @if ($hasActiveFilters)
                <div class="bf-filter-chips">
                    <span class="text-muted small me-1"><i class="bi bi-funnel me-1"></i>Đang lọc:</span>

                    {{-- Chips cho hãng bay --}}
                    @foreach ($filters['airlines'] as $alCode)
                    @php
                    $alMatch = $facets['airlines']->firstWhere('code', $alCode);
                    $alName = $alMatch['name'] ?? $alCode;
                    $remAirlines = array_values(array_diff($filters['airlines'], [$alCode]));
                    $newQuery = request()->query();
                    if (empty($remAirlines)) {
                    unset($newQuery['airlines']);
                    } else {
                    $newQuery['airlines'] = $remAirlines;
                    }
                    unset($newQuery['page']);
                    @endphp
                    <a href="{{ $filterAction . (empty($newQuery) ? '' : '?' . http_build_query($newQuery)) }}"
                        class="bf-filter-chip"
                        title="Bỏ lọc {{ $alName }}">
                        <span>{{ $alName }}</span>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                    @endforeach

                    {{-- Chip cho giá --}}
                    @if ($filters['price_min'] !== null || $filters['price_max'] !== null)
                    @php
                    $priceQuery = request()->except(['price_min', 'price_max', 'page']);
                    $priceLabel = '';
                    if ($filters['price_min'] !== null && $filters['price_max'] !== null) {
                    $priceLabel = number_format($filters['price_min'], 0, ',', '.') . ' - ' . number_format($filters['price_max'], 0, ',', '.') . ' VND';
                    } elseif ($filters['price_min'] !== null) {
                    $priceLabel = 'Từ ' . number_format($filters['price_min'], 0, ',', '.') . ' VND';
                    } else {
                    $priceLabel = 'Đến ' . number_format($filters['price_max'], 0, ',', '.') . ' VND';
                    }
                    @endphp
                    <a href="{{ $filterAction . (empty($priceQuery) ? '' : '?' . http_build_query($priceQuery)) }}"
                        class="bf-filter-chip"
                        title="Bỏ lọc khoảng giá">
                        <span>{{ $priceLabel }}</span>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                    @endif

                    {{-- Chips cho giờ cất cánh --}}
                    @foreach ($filters['dep_time'] as $slotKey)
                    @php
                    $slotLabel = $timeSlotOptions[$slotKey]['label'] ?? $slotKey;
                    $remDep = array_values(array_diff($filters['dep_time'], [$slotKey]));
                    $depQuery = request()->query();
                    if (empty($remDep)) {
                    unset($depQuery['dep_time']);
                    } else {
                    $depQuery['dep_time'] = $remDep;
                    }
                    unset($depQuery['page']);
                    @endphp
                    <a href="{{ $filterAction . (empty($depQuery) ? '' : '?' . http_build_query($depQuery)) }}"
                        class="bf-filter-chip"
                        title="Bỏ lọc giờ cất cánh {{ $slotLabel }}">
                        <span>Đi: {{ $slotLabel }}</span>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                    @endforeach

                    {{-- Chips cho giờ hạ cánh --}}
                    @foreach ($filters['arr_time'] as $slotKey)
                    @php
                    $slotLabel = $timeSlotOptions[$slotKey]['label'] ?? $slotKey;
                    $remArr = array_values(array_diff($filters['arr_time'], [$slotKey]));
                    $arrQuery = request()->query();
                    if (empty($remArr)) {
                    unset($arrQuery['arr_time']);
                    } else {
                    $arrQuery['arr_time'] = $remArr;
                    }
                    unset($arrQuery['page']);
                    @endphp
                    <a href="{{ $filterAction . (empty($arrQuery) ? '' : '?' . http_build_query($arrQuery)) }}"
                        class="bf-filter-chip"
                        title="Bỏ lọc giờ hạ cánh {{ $slotLabel }}">
                        <span>Đến: {{ $slotLabel }}</span>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                    @endforeach

                    {{-- Link xóa toàn bộ bộ lọc --}}
                    <a href="{{ $filterAction }}" class="bf-filter-chip bf-filter-chip--clear">
                        Đặt lại tất cả
                    </a>
                </div>
                @endif

                {{-- VÒNG LẶP RENDER CÁC CHUYẾN BAY --}}
                <div id="bf-flight-list">
                    @forelse ($flights as $flight)
                    @php
                    $depTime = \Carbon\Carbon::parse($flight->getRawOriginal('departure_time'), 'UTC')
                    ->setTimezone($flight->departureAirport->timezone);
                    $arrTime = \Carbon\Carbon::parse($flight->getRawOriginal('arrival_time'), 'UTC')
                    ->setTimezone($flight->arrivalAirport->timezone);
                    $diffMinutes = $depTime->diffInMinutes($arrTime);
                    $hours = intdiv($diffMinutes, 60);
                    $mins = $diffMinutes % 60;
                    $durationText = ($hours > 0 ? "{$hours}h " : "") . ($mins > 0 ? "{$mins}m" : "00m");
                    $airlineName = $flight->aircraft->airline->name ?? 'Hãng hàng không';
                    $aircraftModel = $flight->aircraft->model ?? 'Aircraft';
                    $firstLetter = strtoupper(substr($airlineName, 0, 1));
                    @endphp
                    <article class="bf-flight-card"
                        data-flight-id="{{ $flight->id }}"
                        aria-label="Chuyến bay {{ $airlineName }}">
                        {{-- Hàng trên: Hãng bay & Trạng thái --}}
                        <div class="bf-flight-airline">
                            <div class="bf-airline-info">
                                <div class="bf-airline-logo-badge" aria-hidden="true">
                                    {{ $firstLetter }}
                                </div>
                                <div>
                                    <div class="bf-airline-name">{{ $airlineName }}</div>
                                    <div class="bf-airline-aircraft">{{ $aircraftModel }} &bull; Mã: #FL-{{ str_pad($flight->id, 4, '0', STR_PAD_LEFT) }}</div>
                                </div>
                            </div>

                            <div>
                                @if ($flight->status === 'delayed')
                                <span class="bf-flight-status-badge bf-flight-status-badge--delayed">
                                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Hoãn giờ bay (Delayed)
                                </span>
                                @else
                                <span class="bf-flight-status-badge bf-flight-status-badge--scheduled">
                                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Đúng giờ (Scheduled)
                                </span>
                                @endif
                            </div>
                        </div>

                        {{-- Thân card: Hành trình & Giá vé / Nút chọn --}}
                        <div class="row align-items-center g-3 bf-flight-card-body">
                            {{-- Cột trái: Giờ bay & Trục chặng --}}
                            <div class="col-lg-8">
                                <div class="bf-flight-route-grid">
                                    {{-- Điểm đi --}}
                                    <div class="bf-flight-endpoint">
                                        <div class="bf-flight-time">{{ $depTime->format('H:i') }}</div>
                                        <div class="bf-flight-code">{{ $flight->departureAirport->iata_code }}</div>
                                        <div class="bf-flight-city">{{ $flight->departureAirport->city }}</div>
                                    </div>

                                    {{-- Trục đường bay --}}
                                    <div class="bf-flight-timeline">
                                        <div class="bf-flight-duration">{{ $durationText }}</div>
                                        <div class="bf-flight-track">
                                            <i class="bi bi-airplane bf-flight-track-plane" aria-hidden="true"></i>
                                        </div>
                                        <div class="bf-flight-type">Bay thẳng</div>
                                    </div>

                                    {{-- Điểm đến --}}
                                    <div class="bf-flight-endpoint bf-flight-endpoint--arrival">
                                        <div class="bf-flight-time">{{ $arrTime->format('H:i') }}</div>
                                        <div class="bf-flight-code">{{ $flight->arrivalAirport->iata_code }}</div>
                                        <div class="bf-flight-city">{{ $flight->arrivalAirport->city }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Cột phải: Giá tiền, số ghế và Action POST booking.precheck --}}
                            <div class="col-lg-4">
                                <div class="bf-flight-pricing-block">
                                    <span class="bf-fare-badge">Hạng {{ $fareClass->name }}</span>

                                    <div class="bf-flight-price-wrap">
                                        <span class="bf-price-prefix">Giá từ </span>
                                        <span class="bf-flight-price">{{ number_format($flight->min_price, 0, ',', '.') }}</span>
                                        <span class="bf-flight-price-unit"> VND</span>
                                    </div>

                                    @if ($flight->available_seats <= 5)
                                        <div class="bf-seats-indicator bf-seats-indicator--urgent">
                                        <i class="bi bi-fire text-danger" aria-hidden="true"></i>
                                        <span>Chỉ còn {{ $flight->available_seats }} ghế!</span>
                                </div>
                                @else
                                <div class="bf-seats-indicator">
                                    <i class="bi bi-check2 text-success" aria-hidden="true"></i>
                                    <span>Còn {{ $flight->available_seats }} ghế trống</span>
                                </div>
                                @endif

                                <form action="{{ route('booking.precheck') }}" method="POST" class="w-100">
                                    @csrf
                                    <input type="hidden" name="flight_id" value="{{ $flight->id }}">
                                    <input type="hidden" name="leg" value="{{ $legIndex }}">

                                    <button type="submit" class="bf-btn-select">
                                        <span>Chọn chuyến</span>
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                </div>
                </article>
                @empty
                {{-- 4. TRẠNG THÁI KHÔNG TÌM THẤY KẾT QUẢ (EMPTY STATE - 3 NHÁNH) --}}
                <div class="bf-empty-card">
                    @if ($hasActiveFilters && ($facets['total'] ?? 0) > 0)
                    <div class="bf-empty-icon-box">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                    </div>
                    <h3 class="bf-empty-title">Không có chuyến nào khớp bộ lọc</h3>
                    <p class="bf-empty-desc">
                        Không có chuyến bay nào khớp với hãng bay, mức giá hoặc khung giờ đã chọn.
                        Vui lòng thử điều chỉnh lại tiêu chí hoặc đặt lại bộ lọc.
                    </p>
                    <a href="{{ $filterAction }}" class="btn btn-primary btn-sm px-4 py-2 rounded-pill mt-2">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Đặt lại bộ lọc
                    </a>
                    @elseif ($legIndex > 0)
                    <div class="bf-empty-icon-box">
                        <i class="bi bi-airplane" aria-hidden="true"></i>
                    </div>
                    <h3 class="bf-empty-title">Không tìm thấy chuyến bay phù hợp</h3>
                    <p class="bf-empty-desc">
                        Không có chuyến bay nào từ <strong>{{ $departureAirport->city }} ({{ $departureAirport->iata_code }})</strong>
                        đến <strong>{{ $arrivalAirport->city }} ({{ $arrivalAirport->iata_code }})</strong>
                        trong ngày {{ $depDate->format('d/m/Y') }} khởi hành sau khi chặng trước hạ cánh,
                        với hạng vé <strong>{{ $fareClass->name }}</strong>.
                    </p>
                    <a href="{{ $backUrl }}" class="bf-btn-empty-search text-light">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Chọn lại chuyến của chặng trước
                    </a>
                    @else
                    <div class="bf-empty-icon-box">
                        <i class="bi bi-airplane" aria-hidden="true"></i>
                    </div>
                    <h3 class="bf-empty-title">Không tìm thấy chuyến bay phù hợp</h3>
                    <p class="bf-empty-desc">
                        Rất tiếc, hiện tại không có chuyến bay nào từ
                        <strong>{{ $departureAirport->city }} ({{ $departureAirport->iata_code }})</strong>
                        đến
                        <strong>{{ $arrivalAirport->city }} ({{ $arrivalAirport->iata_code }})</strong>
                        trong ngày {{ $depDate->format('d/m/Y') }} với hạng vé
                        <strong>{{ $fareClass->name }}</strong>.
                        Bạn vui lòng thử chọn ngày bay khác hoặc thay đổi tiêu chí tìm kiếm.
                    </p>
                    <a href="{{ url('/') }}#search-box" class="bf-btn-empty-search text-light">
                        <i class="bi bi-search" aria-hidden="true"></i> Tìm kiếm ngày khác
                    </a>
                    @endif
                </div>
                @endforelse
        </div>

        {{-- 5. PHÂN TRANG (PAGINATION) --}}
        @if ($flights->hasPages())
        <nav class="bf-pagination-wrap" aria-label="Phân trang danh sách chuyến bay">
            {{ $flights->links('pagination::bootstrap-5') }}
        </nav>
        @endif
        </main>
    </div>
</div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/flights.js'])
@endsection