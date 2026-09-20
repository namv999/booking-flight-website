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

        {{-- 3. KHU VỰC NỘI DUNG CHÍNH (SIDEBAR FILTER + FLIGHT LIST) --}}
        <div class="row g-4">
            {{-- SIDEBAR FILTER (BỘ LỌC GIAO DIỆN TRỰC QUAN) --}}
            <aside class="col-lg-3 col-md-4">
                <div class="bf-filter-card">
                    <div class="bf-filter-card__header">
                        <h2 class="bf-filter-card__title">
                            <i class="bi bi-funnel-fill text-primary" aria-hidden="true"></i> Bộ lọc
                        </h2>
                        <a href="javascript:void(0)" class="bf-filter-card__reset" onclick="location.reload()">Đặt lại</a>
                    </div>

                    {{-- Lọc Hãng hàng không --}}
                    <div class="bf-filter-section">
                        <div class="bf-filter-label">
                            <span>Hãng hàng không</span>
                            <i class="bi bi-airplane" aria-hidden="true"></i>
                        </div>
                        <label class="bf-filter-option">
                            <span class="d-flex align-items-center">
                                <input type="checkbox" checked>
                                <span>Vietnam Airlines</span>
                            </span>
                            <span class="bf-filter-badge">VN</span>
                        </label>
                        <label class="bf-filter-option">
                            <span class="d-flex align-items-center">
                                <input type="checkbox" checked>
                                <span>VietJet Air</span>
                            </span>
                            <span class="bf-filter-badge">VJ</span>
                        </label>
                        <label class="bf-filter-option">
                            <span class="d-flex align-items-center">
                                <input type="checkbox" checked>
                                <span>Bamboo Airways</span>
                            </span>
                            <span class="bf-filter-badge">QH</span>
                        </label>
                    </div>

                    {{-- Lọc Điểm dừng --}}
                    <div class="bf-filter-section">
                        <div class="bf-filter-label">
                            <span>Số điểm dừng</span>
                        </div>
                        <label class="bf-filter-option">
                            <span class="d-flex align-items-center">
                                <input type="checkbox" checked>
                                <span>Bay thẳng</span>
                            </span>
                            <span class="bf-filter-badge text-success">Ưu tiên</span>
                        </label>
                    </div>

                    {{-- Lọc Khung giờ cất cánh --}}
                    <div class="bf-filter-section">
                        <div class="bf-filter-label">
                            <span>Giờ cất cánh</span>
                            <i class="bi bi-clock" aria-hidden="true"></i>
                        </div>
                        <div class="bf-time-slots">
                            <div class="bf-time-slot-btn">
                                <strong>Sáng sớm</strong>
                                <span>00:00 - 06:00</span>
                            </div>
                            <div class="bf-time-slot-btn">
                                <strong>Buổi sáng</strong>
                                <span>06:00 - 12:00</span>
                            </div>
                            <div class="bf-time-slot-btn">
                                <strong>Buổi chiều</strong>
                                <span>12:00 - 18:00</span>
                            </div>
                            <div class="bf-time-slot-btn">
                                <strong>Buổi tối</strong>
                                <span>18:00 - 24:00</span>
                            </div>
                        </div>
                    </div>

                    {{-- Ghi chú bộ lọc --}}
                    <div class="text-center pt-2">
                        <small class="text-muted fst-italic" style="font-size: 0.73rem;">
                            * Giá vé hiển thị đã bao gồm thuế và phí ước tính.
                        </small>
                    </div>
                </div>
            </aside>

            {{-- DANH SÁCH CHUYẾN BAY (FLIGHT RESULTS LIST) --}}
            <main class="col-lg-9 col-md-8">
                {{-- Toolbar đếm số lượng + Sắp xếp nhanh --}}
                <div class="bf-results-toolbar">
                    <div class="bf-results-count">
                        Tìm thấy <strong>{{ $flights->total() }}</strong> chuyến bay phù hợp
                    </div>
                    <div class="bf-sort-group d-none d-sm-flex">
                        <span class="text-muted small">Sắp xếp:</span>
                        <span class="bf-sort-pill active">
                            <i class="bi bi-sort-numeric-down me-1"></i>Giá thấp nhất
                        </span>
                        <span class="bf-sort-pill">
                            <i class="bi bi-clock-history me-1"></i>Giờ cất cánh
                        </span>
                    </div>
                </div>

                {{-- VÒNG LẶP RENDER CÁC CHUYẾN BAY --}}
                @forelse ($flights as $flight)
                    @php
                        $depTime = \Carbon\Carbon::parse($flight->departure_time);
                        $arrTime = \Carbon\Carbon::parse($flight->arrival_time);
                        $diffMinutes = $depTime->diffInMinutes($arrTime);
                        $hours = intdiv($diffMinutes, 60);
                        $mins = $diffMinutes % 60;
                        $durationText = ($hours > 0 ? "{$hours}h " : "") . ($mins > 0 ? "{$mins}m" : "00m");
                        $airlineName = $flight->aircraft->airline->name ?? 'Hãng hàng không';
                        $aircraftModel = $flight->aircraft->model ?? 'Aircraft';
                        $firstLetter = strtoupper(substr($airlineName, 0, 1));
                    @endphp
                    <article class="bf-flight-card" aria-label="Chuyến bay {{ $airlineName }}">
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

                            {{-- Cột phải: Giá tiền, số ghế và Action POST booking.hold --}}
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
                                        <input type="hidden" name="fare_class_id" value="{{ $fareClass->id }}">
                                        <input type="hidden" name="adults" value="{{ $adults }}">
                                        <input type="hidden" name="children" value="{{ $children }}">
                                        <input type="hidden" name="infants" value="{{ $infants }}">

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
                    {{-- 4. TRẠNG THÁI KHÔNG TÌM THẤY KẾT QUẢ (EMPTY STATE) --}}
                    <div class="bf-empty-card">
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
                        <a href="{{ url('/') }}#search-box" class="bf-btn-empty-search">
                            <i class="bi bi-search" aria-hidden="true"></i> Tìm kiếm ngày khác
                        </a>
                    </div>
                @endforelse

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
