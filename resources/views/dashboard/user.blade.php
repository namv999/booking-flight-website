{{-- resources/views/dashboard/user.blade.php --}}
{{-- NỘI DUNG dashboard USER - stat cards, danh sách chuyến bay thật, tab lọc trạng thái --}}

<section class="bf-page-hero bf-page-hero--dashboard">
    <div class="bf-container bf-page-hero__inner">
        <div>
            <span class="bf-eyebrow bf-eyebrow--light">Tài khoản của tôi</span>
            <h1>Chào {{ Auth::user()->name }},<br>hành trình mới đang chờ.</h1>
        </div>
        <a class="bf-button bf-button--accent" href="{{ route('home') }}">Đặt chuyến bay mới</a>
    </div>
</section>

<section class="bf-dashboard bf-container">
    <div class="bf-stat-grid">
        <article class="bf-stat-card">
            <span>Chuyến sắp tới</span>
            <strong>{{ str_pad($upcomingCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>Đang chờ khởi hành</small>
        </article>
        <article class="bf-stat-card">
            <span>Hành trình đã đi</span>
            <strong>{{ str_pad($completedCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>Chuyến đã hoàn thành</small>
        </article>
        <article class="bf-stat-card">
            <span>Tổng đơn đặt vé</span>
            <strong>{{ str_pad($totalBookings, 2, '0', STR_PAD_LEFT) }}</strong>
            <small><a href="{{ route('booking-history.index') }}" class="text-decoration-none">Lịch sử đặt vé →</a></small>
        </article>
        <article class="bf-stat-card">
            <span>Hồ sơ hành khách</span>
            <strong>{{ str_pad($savedPassengersCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small><a href="{{ route('saved-passengers.index') }}" class="text-decoration-none">Quản lý hồ sơ →</a></small>
        </article>
    </div>

    <div class="bf-dashboard__layout">
        <div class="bf-dashboard__main">
            <div class="bf-panel-heading">
                <div>
                    <span class="bf-eyebrow">Lịch trình</span>
                    <h2>Chuyến bay của bạn</h2>
                </div>
                <div class="bf-dashboard-tabs" role="tablist" aria-label="Lọc chuyến bay">
                    <button class="bf-dashboard-tabs__button--active" type="button" role="tab" data-trip-filter="upcoming" aria-selected="true">Sắp tới</button>
                    <button type="button" role="tab" data-trip-filter="completed" aria-selected="false">Đã hoàn thành</button>
                </div>
            </div>

            <div class="bf-trip-list" id="trip-list">
                @forelse ($bookings as $booking)
                    @php
                        $firstFlight = $booking->first_flight;
                        $lastFlight = $booking->last_flight;
                        $depAirport = $firstFlight?->departureAirport;
                        $arrAirport = $lastFlight?->arrivalAirport ?? $firstFlight?->arrivalAirport;
                        $airline = $firstFlight?->aircraft?->airline;
                        $depTime = $booking->departure_time_parsed;
                        $arrTime = $booking->arrival_time_parsed;
                        $legCount = $booking->bookingFlights->count();
                        $paxCount = $booking->passengers->count();
                        $tripStatus = $booking->trip_status;
                    @endphp
                    <article class="bf-trip-card" data-trip-status="{{ $tripStatus }}" @if($tripStatus !== 'upcoming') hidden @endif>
                        <div class="bf-trip-card__head">
                            <span class="bf-status {{ $booking->status_class }}">{{ $booking->status_label }}</span>
                            <small>Mã đặt chỗ: #{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</small>
                        </div>
                        <div class="bf-trip-card__route">
                            <div>
                                <strong>{{ $depAirport?->iata_code ?? '---' }}</strong>
                                <span>{{ $depAirport?->city ?? 'Điểm đi' }}</span>
                                <time>{{ $depTime ? $depTime->format('H:i') : '--:--' }}</time>
                            </div>
                            <div class="bf-trip-card__flight">
                                <span>{{ $airline?->iata_code ?? 'FL' }} #{{ str_pad($firstFlight?->id ?? 0, 4, '0', STR_PAD_LEFT) }}</span>
                                <i></i>
                                <small>
                                    @if ($legCount > 1)
                                        Hành trình {{ $legCount }} chặng
                                    @else
                                        Bay thẳng
                                    @endif
                                </small>
                            </div>
                            <div>
                                <strong>{{ $arrAirport?->iata_code ?? '---' }}</strong>
                                <span>{{ $arrAirport?->city ?? 'Điểm đến' }}</span>
                                <time>{{ $arrTime ? $arrTime->format('H:i') : '--:--' }}</time>
                            </div>
                        </div>
                        <div class="bf-trip-card__foot">
                            <span>
                                {{ $depTime ? $depTime->format('d/m/Y') : '' }} &bull; {{ $paxCount }} hành khách
                            </span>
                            @if ($booking->status === 'pending')
                                <a href="{{ route('payment.show', $booking) }}">Hoàn tất thanh toán →</a>
                            @else
                                <a href="{{ route('booking-history.show', $booking->id) }}">Xem chi tiết vé →</a>
                            @endif
                        </div>
                    </article>
                @empty
                    {{-- Không có booking nào --}}
                @endforelse

                {{-- Trạng thái rỗng cho tab Sắp tới --}}
                <div class="bf-trip-empty text-center py-5 border rounded-3 bg-white" id="trip-empty-upcoming" @if($upcomingCount > 0) hidden @endif>
                    <i class="bi bi-airplane text-muted fs-1 mb-2 d-inline-block"></i>
                    <h3 class="h5 fw-bold mb-2" style="color: var(--bf-navy);">Bạn chưa có chuyến bay nào sắp tới</h3>
                    <p class="text-muted small mb-3">Hãy bắt đầu hành trình mới bằng việc tìm kiếm và đặt chuyến bay giá ưu đãi.</p>
                    <a class="bf-button bf-button--accent" href="{{ route('home') }}">Tìm chuyến bay ngay</a>
                </div>

                {{-- Trạng thái rỗng cho tab Đã hoàn thành --}}
                <div class="bf-trip-empty text-center py-5 border rounded-3 bg-white" id="trip-empty-completed" @if($completedCount > 0) hidden @else hidden @endif>
                    <i class="bi bi-clock-history text-muted fs-1 mb-2 d-inline-block"></i>
                    <h3 class="h5 fw-bold mb-2" style="color: var(--bf-navy);">Chưa có chuyến bay nào hoàn thành</h3>
                    <p class="text-muted small mb-3">Các hành trình bạn đã bay sẽ được lưu lại đầy đủ tại đây.</p>
                    <a class="bf-button bf-button--primary" href="{{ route('home') }}">Khám phá điểm đến</a>
                </div>
            </div>
        </div>

        <aside class="bf-dashboard__side">
            <section class="bf-account-card">
                <div class="bf-account-card__head">
                    <span class="bf-avatar bf-avatar--large">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ Auth::user()->name }}</strong>
                        <small class="text-muted">{{ Auth::user()->email }}</small>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2 mt-3 pt-3 border-top">
                    <a class="bf-button bf-button--outline" href="{{ route('profile.edit') }}">
                        <i class="bi bi-gear me-1"></i> Quản lý tài khoản
                    </a>
                    <a class="bf-button bf-button--outline" href="{{ route('saved-passengers.index') }}">
                        <i class="bi bi-people me-1"></i> Hồ sơ hành khách đã lưu
                    </a>
                    <a class="bf-button bf-button--outline" href="{{ route('booking-history.index') }}">
                        <i class="bi bi-ticket-detailed me-1"></i> Lịch sử đặt vé
                    </a>
                </div>
            </section>

            <section class="bf-help-card">
                <span class="bf-eyebrow">Hỗ trợ chuyến đi</span>
                <h2>Bạn cần hỗ trợ lịch trình?</h2>
                <p>Đội ngũ hỗ trợ hoạt động 24/7 để giải đáp thắc mắc và xử lý nhanh chóng.</p>
                <a href="tel:19001234" class="text-decoration-none">
                    <strong class="d-block fs-4 text-primary">1900 1234</strong>
                </a>
            </section>
        </aside>
    </div>
</section>
