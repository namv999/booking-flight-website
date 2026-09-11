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
    {{-- NOTE(C): cần FlightSearchController cung cấp danh sách sân bay và xử lý tìm kiếm; hiện form dùng dữ liệu nhập tĩnh và JS minh họa. --}}
    <form class="bf-flight-search" id="search-box" action="#" novalidate>
        <div class="bf-flight-search__head">
            <div>
                <span class="bf-eyebrow">Tìm chuyến bay</span>
                <h2>Lịch trình của bạn</h2>
            </div>
            <div class="bf-segmented" role="radiogroup" aria-label="Loại hành trình">
                <label><input type="radio" name="trip_type" value="round-trip" checked><span>Khứ hồi</span></label>
                <label><input type="radio" name="trip_type" value="one-way"><span>Một chiều</span></label>
                <label><input type="radio" name="trip_type" value="multi-city"><span>Đa chặng</span></label>
            </div>
        </div>

        <div class="bf-search-grid">
            <label class="bf-field bf-field--route">
                <span>Từ</span>
                <input id="departure-airport" type="text" placeholder="TP. Hồ Chí Minh (SGN)">
                <small>Chọn sân bay khởi hành</small>
            </label>
            <button class="bf-swap-button" id="swap-airports" type="button" aria-label="Đổi điểm đi và điểm đến">⇄</button>
            <label class="bf-field bf-field--route">
                <span>Đến</span>
                <input id="arrival-airport" type="text" placeholder="Hà Nội (HAN)">
                <small>Chọn sân bay đến</small>
            </label>
            <label class="bf-field">
                <span>Ngày đi</span>
                <input id="departure-date" type="date">
            </label>
            <label class="bf-field" id="return-date-field">
                <span>Ngày về</span>
                <input id="return-date" type="date">
            </label>
            <label class="bf-field">
                <span>Hành khách</span>
                <select id="passenger-count">
                    <option>1 người lớn</option>
                    <option>2 người lớn</option>
                    <option>Gia đình (2 + 1)</option>
                    <option>Nhóm 4 người</option>
                </select>
            </label>
            <label class="bf-field">
                <span>Hạng ghế</span>
                <select id="seat-class">
                    <option>Phổ thông</option>
                    <option>Phổ thông đặc biệt</option>
                    <option>Thương gia</option>
                    <option>Hạng nhất</option>
                </select>
            </label>
            <button class="bf-button bf-button--accent bf-search-submit" type="submit">Tìm chuyến bay</button>
        </div>
        <p class="bf-form-message" id="flight-search-message" role="status" aria-live="polite"></p>
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
