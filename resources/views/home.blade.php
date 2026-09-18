{{-- resources/views/home.blade.php --}}
{{-- TRANG CHỦ (route '/') - hero, search box tĩnh, destinations/offers/newsletter/stories (data mẫu, chờ Controller) --}}

@extends('layouts.app')

@section('title', 'Jet Charter Flights - Đặt vé máy bay')

@section('styles')
    @vite(['resources/css/home.css'])
@endsection

@section('content')
<style>
    :root {
        --hero-banner-url: url('{{ asset('images/hero-banner.jpg') }}');
    }
</style>
<section class="bf-hero">
    <div class="bf-hero__image" aria-hidden="true"></div>
    <div class="bf-hero__shade" aria-hidden="true"></div>
    <div class="bf-container bf-hero__content">
        <div class="bf-hero__copy">
            <span class="bf-eyebrow bf-eyebrow--light">Booking Flight</span>
            <h1>Đặt vé máy bay cho hành trình tiếp theo</h1>
            <p>So sánh chuyến bay mẫu, chọn lịch trình phù hợp và bắt đầu chuyến đi với trải nghiệm gọn gàng hơn.</p>
        </div>
        <div class="bf-hero__metrics" aria-label="Thông tin nổi bật">
            <span><strong>24/7</strong><small>Hỗ trợ</small></span>
            <span><strong>120+</strong><small>Đường bay</small></span>
            <span><strong>15%</strong><small>Ưu đãi</small></span>
        </div>
    </div>
</section>

<section class="bf-container bf-search-wrap" aria-label="Tìm kiếm chuyến bay">
    <form class="bf-search-card" id="search-box" action="{{ route('flights.search.results') }}" method="GET">
        @if ($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="bf-trip-tabs">
                <input type="radio" class="btn-check" name="trip_type" id="trip-oneway" value="one-way" checked>
                <label class="bf-tab-btn" for="trip-oneway">Một chiều</label>

                <input type="radio" class="btn-check" name="trip_type" id="trip-roundtrip" value="round-trip">
                <label class="bf-tab-btn" for="trip-roundtrip">Khứ hồi</label>

                <input type="radio" class="btn-check" name="trip_type" id="trip-multicity" value="multi-city">
                <label class="bf-tab-btn" for="trip-multicity">Đa chặng</label>
            </div>

            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button type="button" class="btn bf-dropdown-btn dropdown-toggle" id="passenger-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-people me-1"></i>
                        <span id="passenger-summary">1 Người lớn, 0 Trẻ em, 0 Em bé</span>
                    </button>
                    <div class="dropdown-menu p-3 shadow-sm border-0" style="width: 280px; border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="fw-semibold small">Người lớn</div>
                                <div class="small text-muted">Từ 12 tuổi</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle bf-stepper-btn" data-target="adults" data-step="-1">−</button>
                                <input type="number" class="form-control form-control-sm text-center" style="width:52px" name="adults" id="adults-input" value="1" min="1" max="9">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle bf-stepper-btn" data-target="adults" data-step="1">+</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="fw-semibold small">Trẻ em</div>
                                <div class="small text-muted">Từ 2 - 11 tuổi</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle bf-stepper-btn" data-target="children" data-step="-1">−</button>
                                <input type="number" class="form-control form-control-sm text-center" style="width:52px" name="children" id="children-input" value="0" min="0" max="9">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle bf-stepper-btn" data-target="children" data-step="1">+</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="fw-semibold small">Em bé</div>
                                <div class="small text-muted">Dưới 2 tuổi</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle bf-stepper-btn" data-target="infants" data-step="-1">−</button>
                                <input type="number" class="form-control form-control-sm text-center" style="width:52px" name="infants" id="infants-input" value="0" min="0" max="9">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle bf-stepper-btn" data-target="infants" data-step="1">+</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm w-100 rounded-3" id="passenger-done">Xong</button>
                    </div>
                </div>

                <div class="position-relative">
                    <i class="bi bi-journal-bookmark position-absolute top-50 start-0 translate-middle-y ms-2 text-muted" style="z-index: 5;"></i>
                    <select class="form-select bf-dropdown-btn ps-4" name="fare_class_id" required>
                        @foreach ($fareClasses as $fareClass)
                            <option value="{{ $fareClass->id }}" {{ old('fare_class_id') == $fareClass->id ? 'selected' : '' }}>
                                {{ $fareClass->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-2 align-items-center mb-3 position-relative">
            <!-- Từ (Điểm đi) -->
            <div class="col-12 col-md-5 position-relative">
                <label class="form-label text-muted small mb-1 ms-1">Từ</label>
                <div class="dropdown">
                    <input type="text" class="form-control bf-input dropdown-toggle" id="departure-display" data-bs-toggle="dropdown" placeholder="Chọn điểm đi" readonly autocomplete="off" required>
                    <input type="hidden" name="departure_airport_id" id="departure-airport" value="{{ old('departure_airport_id') }}">
                    
                    <!-- Popup gợi ý danh sách kiểu VNA -->
                    <div class="dropdown-menu p-3 shadow-lg border-0 rounded-4" id="departure-dropdown-menu" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control bg-light border-0" id="departure-search-input" placeholder="Tìm kiếm...">
                        </div>
                        <div class="airport-list">
                            @foreach ($airports as $airport)
                                <div class="departure-airport-option d-flex justify-content-between align-items-center p-2 rounded-3 cursor-pointer hover-bg-light" 
                                    data-id="{{ $airport->id }}" 
                                    data-code="{{ $airport->iata_code }}" 
                                    data-city="{{ $airport->city }}">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $airport->city }}</div>
                                        <div class="small text-muted">{{ $airport->country ?? 'Việt Nam' }}</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">{{ $airport->iata_code }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút Swap -->
            <div class="col-12 col-md-2 d-flex justify-content-center align-items-end bf-swap-wrapper">
                <button class="bf-swap-btn" id="swap-airports" type="button" aria-label="Đổi điểm đi và điểm đến">
                    <i class="bi bi-arrow-left-right"></i>
                </button>
            </div>

            <!-- Đến (Điểm đến) -->
            <div class="col-12 col-md-5 position-relative">
                <label class="form-label text-muted small mb-1 ms-1">Đến</label>
                <div class="dropdown">
                    <input type="text" class="form-control bf-input dropdown-toggle" id="arrival-display" data-bs-toggle="dropdown" placeholder="Chọn điểm đến" readonly autocomplete="off" required>
                    <input type="hidden" name="arrival_airport_id" id="arrival-airport" value="{{ old('arrival_airport_id') }}">
                    
                    <!-- Popup gợi ý danh sách kiểu VNA -->
                    <div class="dropdown-menu p-3 shadow-lg border-0 rounded-4" id="arrival-dropdown-menu" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control bg-light border-0" id="arrival-search-input" placeholder="Tìm kiếm...">
                        </div>
                        <div class="airport-list">
                            @foreach ($airports as $airport)
                                <div class="arrival-airport-option d-flex justify-content-between align-items-center p-2 rounded-3 cursor-pointer hover-bg-light" 
                                    data-id="{{ $airport->id }}" 
                                    data-code="{{ $airport->iata_code }}" 
                                    data-city="{{ $airport->city }}">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $airport->city }}</div>
                                        <div class="small text-muted">{{ $airport->country ?? 'Việt Nam' }}</div>
                                    </div>
                                    <span class="badge bg-light text-dark border">{{ $airport->iata_code }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ngày đi -->
            <div class="col-6 col-md-6 mt-2">
                <label class="form-label text-muted small mb-1 ms-1">Ngày đi</label>
                <div class="position-relative">
                    <i class="bi bi-calendar3 bf-date-icon"></i>
                    <input type="date" class="form-control bf-input bf-date-input" name="departure_date" id="departure-date" value="{{ old('departure_date') }}" required>
                </div>
            </div>

            <!-- Ngày về -->
            <div class="col-6 col-md-6 mt-2">
                <label class="form-label text-muted small mb-1 ms-1">Ngày về</label>
                <div class="position-relative">
                    <i class="bi bi-calendar3 bf-date-icon"></i>
                    <input type="date" class="form-control bf-input bf-date-input" id="return-date" disabled>
                </div>
            </div>
        </div>

        <button class="btn bf-search-btn w-100" type="submit">
            <i class="bi bi-search me-2"></i>Tìm chuyến bay
        </button>
    </form>
</section>

<section class="bf-section bf-benefit-band">
    <div class="bf-container bf-benefit-grid">
        <article class="bf-benefit-item"><span>01</span><div><strong>Giá minh bạch</strong><p>Hiển thị rõ chi phí ngay từ đầu.</p></div></article>
        <article class="bf-benefit-item"><span>02</span><div><strong>Đặt vé nhanh</strong><p>Quy trình gọn trong vài bước.</p></div></article>
        <article class="bf-benefit-item"><span>03</span><div><strong>Hỗ trợ 24/7</strong><p>Luôn sẵn sàng khi bạn cần.</p></div></article>
        <article class="bf-benefit-item"><span>04</span><div><strong>Đối tác tin cậy</strong><p>Nhiều hãng bay để lựa chọn.</p></div></article>
    </div>
</section>

<section class="bf-section bf-destinations">
    <div class="bf-container">
        <div class="bf-section-heading">
            <div><span class="bf-eyebrow">Điểm đến nổi bật</span><h2>Chạm tới những nơi đáng nhớ</h2></div>
            <a class="bf-text-link" href="#">Xem tất cả <span aria-hidden="true">→</span></a>
        </div>
        {{-- NOTE(C): cần Controller truyền $destinations (Collection); hiện đang dùng 5 điểm đến mẫu viết cứng. --}}
        <div class="bf-destination-grid">
            <a class="bf-destination-card bf-destination-card--featured" href="#">
                <img src="{{ asset('images/destination-da-nang.jpg') }}" alt="Bờ biển Đà Nẵng">
                <span class="bf-destination-card__shade"></span>
                <span class="bf-destination-card__content"><small>Biển và thành phố</small><strong>Đà Nẵng</strong><em>Từ 1.290.000đ</em></span>
            </a>
            <a class="bf-destination-card" href="#">
                <img src="{{ asset('images/destination-ha-noi.jpg') }}" alt="Thành phố Hà Nội">
                <span class="bf-destination-card__shade"></span>
                <span class="bf-destination-card__content"><small>Nét đẹp ngàn năm</small><strong>Hà Nội</strong><em>Từ 1.090.000đ</em></span>
            </a>
            <a class="bf-destination-card" href="#">
                <img src="{{ asset('images/destination-bangkok.jpg') }}" alt="Thành phố Bangkok">
                <span class="bf-destination-card__shade"></span>
                <span class="bf-destination-card__content"><small>Rực rỡ ngày đêm</small><strong>Bangkok</strong><em>Từ 2.890.000đ</em></span>
            </a>
            <a class="bf-destination-card" href="#">
                <img src="{{ asset('images/destination-tokyo.jpg') }}" alt="Thành phố Tokyo">
                <span class="bf-destination-card__shade"></span>
                <span class="bf-destination-card__content"><small>Nhịp sống tương lai</small><strong>Tokyo</strong><em>Từ 7.490.000đ</em></span>
            </a>
            <a class="bf-destination-card" href="#">
                <img src="{{ asset('images/destination-ho-chi-minh.jpg') }}" alt="Thành phố Hồ Chí Minh">
                <span class="bf-destination-card__shade"></span>
                <span class="bf-destination-card__content"><small>Năng động và trẻ</small><strong>TP. Hồ Chí Minh</strong><em>Từ 990.000đ</em></span>
            </a>
        </div>
    </div>
</section>

<section class="bf-section bf-offers">
    <div class="bf-container bf-offers__grid">
        <div class="bf-offers__intro">
            <span class="bf-eyebrow">Ưu đãi trong tháng</span>
            <h2>Sẵn sàng cho hành trình tiếp theo?</h2>
            <p>Đăng ký nhận thông tin giá tốt và những điểm đến đang được yêu thích.</p>
            {{-- NOTE(C): cần NewsletterController lưu email; hiện form chỉ kiểm tra định dạng bằng JS và hiển thị thông báo mẫu. --}}
            <form class="bf-newsletter" id="newsletter-form" action="#" novalidate>
                <label class="bf-sr-only" for="newsletter-email">Email nhận ưu đãi</label>
                <input id="newsletter-email" type="email" placeholder="Email của bạn">
                <button class="bf-button bf-button--accent" type="submit">Đăng ký</button>
            </form>
            <p class="bf-form-message" id="newsletter-message" role="status" aria-live="polite"></p>
        </div>
        {{-- NOTE(C): cần Controller truyền $promotions; hiện đang dùng nội dung ưu đãi mẫu viết cứng. --}}
        <div class="bf-offer-ticket">
            <span class="bf-offer-ticket__label">Ưu đãi giới hạn</span>
            <strong>Giảm 15%</strong>
            <p>Cho hành trình nội địa đặt trước 30 ngày.</p>
            <div><span>Mã ưu đãi</span><b>FLY15</b></div>
        </div>
    </div>
</section>

<section class="bf-section bf-stories">
    <div class="bf-container">
        <div class="bf-section-heading">
            <div><span class="bf-eyebrow">Cẩm nang hành trình</span><h2>Bay thông minh, trải nghiệm nhiều hơn</h2></div>
        </div>
        {{-- NOTE(C): cần Controller truyền $articles (Collection); hiện đang dùng 2 bài viết mẫu viết cứng. --}}
        <div class="bf-story-grid">
            <article class="bf-story-card">
                <img src="{{ asset('images/article-charter-flight.avif') }}" alt="Máy bay trên bầu trời">
                <div><small>Kinh nghiệm bay</small><h3>Chuẩn bị gì cho một chuyến bay thật nhẹ nhàng?</h3><a href="#">Đọc bài viết <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="bf-story-card">
                <img src="{{ asset('images/article-travel-guide.png') }}" alt="Hành trình khám phá điểm đến mới">
                <div><small>Gợi ý điểm đến</small><h3>5 hành trình đáng để lên kế hoạch trong mùa hè này</h3><a href="#">Đọc bài viết <span aria-hidden="true">→</span></a></div>
            </article>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    @vite(['resources/js/home.js'])
@endsection