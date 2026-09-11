<section class="bf-admin-heading">
    <div><span class="bf-eyebrow">Tổng quan vận hành</span><h1>Chào buổi sáng, Admin</h1><p>Cập nhật nhanh tình hình đặt vé và chuyến bay hôm nay.</p></div>
    <div class="bf-admin-heading__actions"><button class="bf-button bf-button--outline" type="button">Xuất báo cáo</button><button class="bf-button bf-button--primary" type="button">Thêm chuyến bay</button></div>
</section>

{{-- NOTE(C): cần AdminDashboardController truyền doanh thu, booking, khách hàng và chuyến bay; hiện đang dùng số liệu mẫu viết cứng. --}}
<section class="bf-admin-stat-grid" aria-label="Chỉ số vận hành">
    <article><span>Doanh thu hôm nay</span><strong>186,4 tr</strong><small class="bf-admin-stat__trend--positive">+12,8% so với hôm qua</small></article>
    <article><span>Đặt vé mới</span><strong>248</strong><small class="bf-admin-stat__trend--positive">+18 đơn trong 2 giờ</small></article>
    <article><span>Khách hàng mới</span><strong>76</strong><small>1.842 khách hàng hoạt động</small></article>
    <article><span>Chuyến bay hôm nay</span><strong>42</strong><small class="bf-admin-stat__trend--warning">3 chuyến cần theo dõi</small></article>
</section>

<section class="bf-admin-grid">
    <div class="bf-admin-panel bf-admin-panel--wide">
        <div class="bf-panel-heading"><div><span class="bf-eyebrow">7 ngày gần nhất</span><h2>Xu hướng đặt vé</h2></div><select aria-label="Khoảng thời gian"><option>7 ngày</option><option>30 ngày</option></select></div>
        {{-- NOTE(C): cần AdminDashboardController truyền dữ liệu biểu đồ booking theo ngày; hiện dùng cột CSS và số liệu mẫu, chưa tích hợp Chart.js. --}}
        <div class="bf-chart" aria-label="Biểu đồ đặt vé mẫu">
            <div><span style="height: 48%"></span><small>Thứ 2</small></div><div><span style="height: 62%"></span><small>Thứ 3</small></div><div><span style="height: 54%"></span><small>Thứ 4</small></div><div><span style="height: 78%"></span><small>Thứ 5</small></div><div><span style="height: 68%"></span><small>Thứ 6</small></div><div><span style="height: 92%"></span><small>Thứ 7</small></div><div><span style="height: 84%"></span><small>CN</small></div>
        </div>
    </div>

    <aside class="bf-admin-panel">
        <div class="bf-panel-heading"><div><span class="bf-eyebrow">Trạng thái</span><h2>Vận hành hôm nay</h2></div></div>
        <div class="bf-operation-list"><div><span class="bf-dot bf-dot--success"></span><p><strong>36 chuyến đúng giờ</strong><small>85,7% tổng chuyến</small></p></div><div><span class="bf-dot bf-dot--warning"></span><p><strong>3 chuyến trễ</strong><small>Đang cập nhật hành khách</small></p></div><div><span class="bf-dot"></span><p><strong>3 chuyến chờ cất cánh</strong><small>Trong 90 phút tới</small></p></div></div>
    </aside>

    <div class="bf-admin-panel bf-admin-panel--full">
        <div class="bf-panel-heading"><div><span class="bf-eyebrow">Mới nhất</span><h2>Đặt vé gần đây</h2></div><a class="bf-text-link" href="#">Xem tất cả →</a></div>
        {{-- NOTE(C): cần AdminDashboardController truyền $recentBookings (Collection); hiện đang dùng 4 booking mẫu viết cứng. --}}
        <div class="bf-table-wrap"><table class="bf-table"><thead><tr><th>Mã đặt chỗ</th><th>Khách hàng</th><th>Hành trình</th><th>Ngày bay</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody><tr><td><strong>JCF8X2</strong></td><td>Nguyễn Minh Anh</td><td>SGN → HAN</td><td>18/09/2026</td><td>2.480.000đ</td><td><span class="bf-status bf-status--confirmed">Đã xác nhận</span></td></tr><tr><td><strong>JCF4P9</strong></td><td>Trần Hoàng Nam</td><td>DAD → BKK</td><td>04/10/2026</td><td>5.920.000đ</td><td><span class="bf-status bf-status--pending">Chờ thanh toán</span></td></tr><tr><td><strong>JCF7M1</strong></td><td>Lê Thu Trang</td><td>HAN → NRT</td><td>22/09/2026</td><td>11.360.000đ</td><td><span class="bf-status bf-status--confirmed">Đã xác nhận</span></td></tr><tr><td><strong>JCF3K6</strong></td><td>Phạm Đức Long</td><td>SGN → SIN</td><td>25/09/2026</td><td>4.150.000đ</td><td><span class="bf-status">Đang xử lý</span></td></tr></tbody></table></div>
    </div>
</section>
