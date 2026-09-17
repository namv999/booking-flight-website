{{-- resources/views/dashboard/user.blade.php --}}
{{-- NỘI DUNG dashboard USER - stat cards, danh sách chuyến bay, tab lọc trạng thái (data mẫu, chờ Controller) --}}

<section class="bf-page-hero bf-page-hero--dashboard">
    <div class="bf-container bf-page-hero__inner">
        <div>
            <span class="bf-eyebrow bf-eyebrow--light">Tài khoản của tôi</span>
            <h1>Chào {{ Auth::user()->name }},<br>hành trình mới đang chờ.</h1>
        </div>
        <a class="bf-button bf-button--accent" href="/">Đặt chuyến bay mới</a>
    </div>
</section>

<section class="bf-dashboard bf-container">
    {{-- NOTE(C): cần DashboardController truyền số lượng booking, điểm thưởng và voucher; hiện đang dùng số liệu mẫu viết cứng. --}}
    <div class="bf-stat-grid">
        <article class="bf-stat-card"><span>Chuyến sắp tới</span><strong>02</strong><small>Chuyến gần nhất sau 6 ngày</small></article>
        <article class="bf-stat-card"><span>Hành trình đã đi</span><strong>08</strong><small>Trong 12 tháng gần đây</small></article>
        <article class="bf-stat-card"><span>Điểm Jet Rewards</span><strong>2.480</strong><small>Còn 520 điểm để nâng hạng</small></article>
        <article class="bf-stat-card"><span>Ưu đãi của bạn</span><strong>03</strong><small>Có 1 ưu đãi sắp hết hạn</small></article>
    </div>

    <div class="bf-dashboard__layout">
        <div class="bf-dashboard__main">
            <div class="bf-panel-heading">
                <div><span class="bf-eyebrow">Lịch trình</span><h2>Chuyến bay của bạn</h2></div>
                <div class="bf-dashboard-tabs" role="tablist" aria-label="Lọc chuyến bay">
                    <button class="bf-dashboard-tabs__button--active" type="button" role="tab" data-trip-filter="upcoming">Sắp tới</button>
                    <button type="button" role="tab" data-trip-filter="completed">Đã hoàn thành</button>
                </div>
            </div>

            {{-- NOTE(C): cần DashboardController truyền $bookings (Collection); hiện đang dùng hai booking mẫu viết cứng. --}}
            <div class="bf-trip-list" id="trip-list">
                <article class="bf-trip-card" data-trip-status="upcoming">
                    <div class="bf-trip-card__head"><span class="bf-status bf-status--confirmed">Đã xác nhận</span><small>Mã đặt chỗ: JCF8X2</small></div>
                    <div class="bf-trip-card__route">
                        <div><strong>SGN</strong><span>TP. Hồ Chí Minh</span><time>07:30</time></div>
                        <div class="bf-trip-card__flight"><span>VN 214</span><i></i><small>2 giờ 10 phút · Bay thẳng</small></div>
                        <div><strong>HAN</strong><span>Hà Nội</span><time>09:40</time></div>
                    </div>
                    <div class="bf-trip-card__foot"><span>Thứ Sáu, 18 tháng 09 · Phổ thông</span><a href="#">Xem chi tiết →</a></div>
                </article>
                <article class="bf-trip-card" data-trip-status="upcoming">
                    <div class="bf-trip-card__head"><span class="bf-status bf-status--pending">Chờ thanh toán</span><small>Mã đặt chỗ: JCF4P9</small></div>
                    <div class="bf-trip-card__route">
                        <div><strong>DAD</strong><span>Đà Nẵng</span><time>13:20</time></div>
                        <div class="bf-trip-card__flight"><span>QH 105</span><i></i><small>1 giờ 25 phút · Bay thẳng</small></div>
                        <div><strong>BKK</strong><span>Bangkok</span><time>14:45</time></div>
                    </div>
                    <div class="bf-trip-card__foot"><span>Chủ Nhật, 04 tháng 10 · Phổ thông</span><a href="#">Hoàn tất thanh toán →</a></div>
                </article>
                <article class="bf-trip-card" data-trip-status="completed" hidden>
                    <div class="bf-trip-card__head"><span class="bf-status">Đã hoàn thành</span><small>Mã đặt chỗ: JCF2H7</small></div>
                    <div class="bf-trip-card__route">
                        <div><strong>HAN</strong><span>Hà Nội</span><time>09:10</time></div>
                        <div class="bf-trip-card__flight"><span>VN 631</span><i></i><small>1 giờ 25 phút · Bay thẳng</small></div>
                        <div><strong>DAD</strong><span>Đà Nẵng</span><time>10:35</time></div>
                    </div>
                    <div class="bf-trip-card__foot"><span>Thứ Hai, 20 tháng 07 · Phổ thông</span><a href="#">Đặt lại chuyến này →</a></div>
                </article>
            </div>
        </div>

        <aside class="bf-dashboard__side">
            <section class="bf-account-card">
                <div class="bf-account-card__head"><span class="bf-avatar bf-avatar--large">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span><div><strong>{{ Auth::user()->name }}</strong><small>Thành viên Jet Explorer</small></div></div>
                <div class="bf-progress"><span><i style="width: 72%"></i></span><small>520 điểm nữa để lên hạng Jet Plus</small></div>
                <a class="bf-button bf-button--outline" href="{{ route('profile.edit') }}">Quản lý hồ sơ</a>
            </section>
            <section class="bf-help-card">
                <span class="bf-eyebrow">Hỗ trợ chuyến đi</span>
                <h2>Bạn cần thay đổi lịch trình?</h2>
                <p>Đội ngũ hỗ trợ hoạt động 24/7 để giúp bạn xử lý nhanh.</p>
                <strong>1900 1234</strong>
            </section>
        </aside>
    </div>
</section>
