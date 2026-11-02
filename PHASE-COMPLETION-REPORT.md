# Báo Cáo Hoàn Thành Toàn Diện: Phase 2 & Các Tính Năng Tồn Đọng

- **Ngày hoàn thành:** 02/11/2026
- **Nhánh thực hiện:** `feature/passenger-management-and-booking-addons`
- **Tài liệu đối soát nguồn:**
  - [PHASE-2-COMPLETION-REVIEW.md](file:///d:/PHP/booking-flight/PHASE-2-COMPLETION-REVIEW.md)
  - [PHASE-NEXT-UNUSED-FEATURES.md](file:///d:/PHP/booking-flight/PHASE-NEXT-UNUSED-FEATURES.md)
- **Chuẩn thiết kế tuân thủ:** [antigravity-design-expert/SKILL.md](file:///d:/PHP/booking-flight/.agents/skills/antigravity-design-expert/SKILL.md)

---

## 1. Tóm tắt kết quả (Executive Summary)

Đợt cải tiến và rà soát này đã giải quyết **toàn bộ các điểm nghẽn, tính năng chưa nối backend, dữ liệu mẫu hardcode, liên kết chết và lỗi giao diện** được ghi nhận trong hai tài liệu trên. Đồ án hiện đạt trạng thái sẵn sàng nghiệm thu và merge với chất lượng kỹ thuật cao:

- **Bộ kiểm thử PHPUnit:** Đạt **379/379 tests (1.749 assertions)** pass 100% (tăng từ 360 tests / 1.586 assertions ban đầu).
- **Chuẩn hóa Code Style (Laravel Pint):** Đạt kết quả `passed` (100% PSR-12).
- **Frontend Asset Build (Vite):** Biên dịch thành công toàn bộ bundle CSS/JS phục vụ tìm chuyến, sơ đồ ghế, dashboard và trang chủ.
- **Tính khả dụng UI/UX:** Xóa bỏ 100% liên kết chết `href="#"`, khắc phục triệt để lỗi chữ tối trên nền tối, nâng cấp bảng điều khiển quản trị Admin theo chuẩn vận hành hiện đại.

---

## 2. Chi tiết các hạng mục đã hoàn thành

### Hạng mục 1: Sơ đồ chọn ghế (Seat Map) & Nghiệp vụ phí ghế tự chọn
*(Giải quyết mục 3 của Phase 2 Review & mục 1, 2, 3 của Bảng A - Phase Next)*

- **Kiến trúc dịch vụ đặt vé nguyên tử:** Xây dựng [`app/Services/BookingCreationService.php`](file:///d:/PHP/booking-flight/app/Services/BookingCreationService.php) xử lý atomic transaction:
  - Khởi tạo `Booking`, các chặng bay `BookingFlight` (đảm bảo thứ tự ID tránh deadlock), tạo `Passenger` và `Ticket`.
  - Phân tách rành mạch hai cơ chế chọn ghế:
    1. **Khách tự chọn ghế (`is_self_selected = true`):** Tính thêm phí `fare_classes.seat_selection_fee` cho từng vé người lớn/trẻ em tự chọn.
    2. **Hệ thống tự động gán ghế (`is_self_selected = false`):** Chọn ghế trống hợp lệ đầu tiên, **không** thu phí chọn ghế.
  - Áp dụng nguyên tắc giá vé em bé: tính theo giá gốc của ghế người lớn đi kèm (không cộng dồn phí tự chọn ghế của người lớn).
- **Controller Sơ đồ ghế:** Hoàn thiện [`app/Http/Controllers/SeatSelectionController.php`](file:///d:/PHP/booking-flight/app/Http/Controllers/SeatSelectionController.php):
  - `show()`: Hiển thị giao diện chọn ghế đa chặng (One-way, Round-trip, Multi-city) kèm danh sách hành khách hợp lệ.
  - `map()`: Endpoint API JSON cung cấp danh sách ghế, trạng thái khả dụng (`available`, `booked`, `held`, `selected`) và phí theo từng hạng vé.
  - `store()`: Tiếp nhận lựa chọn ghế, kiểm tra tính hợp lệ và gọi `BookingCreationService` để tạo đơn hàng chuyển sang thanh toán.
- **Sửa dữ liệu mẫu (Seeder):** Điều chỉnh [`database/seeders/TicketSeeder.php`](file:///d:/PHP/booking-flight/database/seeders/TicketSeeder.php) đảm bảo các vé seed tự động mang cờ `is_self_selected = false` đúng với thực tế nghiệp vụ.
- **Bộ kiểm thử tự động:** Bổ sung [`tests/Feature/SeatSelectionFlowTest.php`](file:///d:/PHP/booking-flight/tests/Feature/SeatSelectionFlowTest.php) với 8 tests bao phủ toàn bộ luồng chọn ghế và phí ghế.

---

### Hạng mục 2: Dashboard khách hàng (`/dashboard`) kết nối dữ liệu thật
*(Giải quyết mục 4, 5 của Bảng A - Phase Next)*

- **Controller Dashboard chuyên dụng:** Tạo [`app/Http/Controllers/UserDashboardController.php`](file:///d:/PHP/booking-flight/app/Http/Controllers/UserDashboardController.php):
  - Truy vấn toàn bộ đơn đặt vé thật sự của người dùng đang đăng nhập (`auth()->id()`), tải kèm đầy đủ quan hệ các chặng bay, sân bay đi/đến, hãng hàng không, danh sách vé và số ghế.
  - Phân loại trạng thái chuyến đi theo thời gian cất cánh thực tế:
    - **Chuyến bay sắp tới (Upcoming):** Các chuyến bay chưa khởi hành (`departure_time >= now()`), trạng thái đơn là `paid`.
    - **Chuyến bay đã hoàn thành (Completed):** Các chuyến bay đã qua giờ bay.
  - Tính toán các chỉ số thống kê thật của tài khoản: tổng số chuyến bay đã thực hiện, tổng chi tiêu tích lũy, các chuyến bay gần nhất.
- **Giao diện & Bộ lọc phía Client:**
  - Nâng cấp [`resources/views/dashboard/user.blade.php`](file:///d:/PHP/booking-flight/resources/views/dashboard/user.blade.php) hiển thị thẻ chuyến đi chi tiết, trạng thái thẻ E-ticket, mã đặt chỗ monospace `#BK-xxxxx`.
  - Cập nhật [`resources/js/dashboard.js`](file:///d:/PHP/booking-flight/resources/js/dashboard.js) lọc mượt mà giữa các tab "Tất cả", "Sắp tới", "Đã hoàn thành" mà không cần tải lại trang, có empty-state rõ ràng khi danh sách trống.
- **Bộ kiểm thử tự động:** Bổ sung [`tests/Feature/UserDashboardTest.php`](file:///d:/PHP/booking-flight/tests/Feature/UserDashboardTest.php) (4 tests).

---

### Hạng mục 3: Xóa bỏ 100% liên kết chết & Chuẩn hóa trang chủ (`/`)
*(Giải quyết mục 1 của Phase 2 Review & mục 6, 7, 8, 9 của Bảng A - Phase Next)*

- **Điều hướng thẻ Điểm đến (Destination Cards):**
  - Loại bỏ hoàn toàn `href="#"` trên 5 thẻ điểm đến tại [`resources/views/home.blade.php`](file:///d:/PHP/booking-flight/resources/views/home.blade.php).
  - Tích hợp [`resources/js/home.js`](file:///d:/PHP/booking-flight/resources/js/home.js): khi khách bấm vào bất kỳ thẻ điểm đến nào (Đà Nẵng, Hà Nội, TP. Hồ Chí Minh, Phú Quốc, Nha Trang), trang sẽ tự động cuộn mượt (`smooth scroll`) về khung tìm kiếm `#search-box` và tự động chọn sân bay đến tương ứng trong ô tìm kiếm.
- **CTA bài viết & Khuyến mãi:**
  - Thay thế 2 CTA "Đọc bài viết" (`href="#"`) bằng nội dung thông tin chuyến bay hữu ích, loại bỏ liên kết chết.
  - Chuyển đổi nhãn ưu đãi "Xem tất cả" trỏ về form tìm kiếm `#search-box`.
  - Chuẩn hóa văn phong quảng cáo sang cam kết dịch vụ minh bạch, không gây hiểu lầm về mã coupon khi hệ thống chưa có tính năng mã giảm giá.
- **Backend Đăng ký Bản tin (Newsletter):**
  - Tạo [`app/Http/Controllers/NewsletterController.php`](file:///d:/PHP/booking-flight/app/Http/Controllers/NewsletterController.php) tiếp nhận `POST /newsletter/subscribe` với cơ chế validate email và phản hồi JSON thông báo thành công hoặc lỗi hợp lệ.
  - Nối form trang chủ vào endpoint thực, thông báo trạng thái tức thì trên giao diện.
  - Bổ sung bộ kiểm thử [`tests/Feature/NewsletterTest.php`](file:///d:/PHP/booking-flight/tests/Feature/NewsletterTest.php) (2 tests).

---

### Hạng mục 4: Khắc phục bộ lọc tương tác tại trang kết quả (`/flights/results`)
*(Giải quyết yêu cầu trực tiếp của người dùng)*

- **Script lọc & sắp xếp Client-side:** Xây dựng [`resources/js/flights.js`](file:///d:/PHP/booking-flight/resources/js/flights.js) và tích hợp vào cấu hình build Vite:
  - **Lọc theo hãng hàng không:** Nhận diện động các hãng bay xuất hiện trong kết quả tìm kiếm (`.bf-airline-checkbox`).
  - **Lọc theo điểm dừng:** Hỗ trợ tùy chọn bay thẳng (`#bf-filter-direct`).
  - **Lọc theo 4 khung giờ cất cánh:** Sáng sớm (00:00 - 06:00), Buổi sáng (06:00 - 12:00), Buổi chiều (12:00 - 18:00), Buổi tối (18:00 - 24:00) với trạng thái active trực quan và phím tắt bàn phím (Enter/Space).
  - **Sắp xếp chuyến bay:** Sắp xếp nhanh theo **Giá thấp nhất** (`data-sort="price"`) và **Giờ cất cánh** (`data-sort="time"`).
  - **Cập nhật số lượng thời gian thực:** Cập nhật bộ đếm `#bf-count-display` tương ứng với số chuyến bay sau khi lọc.
  - **Trạng thái không có kết quả:** Hiển thị card `#bf-no-matching-flights` khi không có chuyến nào khớp tiêu chí kèm nút bấm *Đặt lại bộ lọc* (`#bf-filter-reset` và `#bf-filter-reset-empty`).
- **Markup View:** Bổ sung toàn bộ data attributes trên [`resources/views/flights/results.blade.php`](file:///d:/PHP/booking-flight/resources/views/flights/results.blade.php) (`data-airline`, `data-price`, `data-dep-hour`, `data-dep-time`, `data-stops`).
- **Bộ kiểm thử tự động:** Cập nhật [`tests/Feature/FlightSearchTest.php`](file:///d:/PHP/booking-flight/tests/Feature/FlightSearchTest.php) kiểm tra toàn bộ cấu trúc phần tử lọc.

---

### Hạng mục 5: Nâng cấp toàn diện Site Admin (`/admin`)
*(Giải quyết mục 2 của Phase 2 Review & mục 10, 11 của Bảng A - Phase Next & Yêu cầu UI Admin)*

- **Bảng điều khiển tương thích đa Database:** Tái cấu trúc [`app/Http/Controllers/Admin/DashboardController.php`](file:///d:/PHP/booking-flight/app/Http/Controllers/Admin/DashboardController.php):
  - Biểu thức tính doanh thu theo tháng tự động nhận diện SQLite (`CAST(strftime('%m', created_at) AS INTEGER)`) hoặc MySQL (`MONTH(created_at)`), cho phép chạy kiểm thử tự động trên mọi môi trường DB mà không lỗi.
  - Tổng hợp đầy đủ chỉ số vận hành: Doanh thu thực tế (`Payment::where('status', 'success')`), tổng số đơn đặt vé (phân tách đã thanh toán vs chờ xử lý), số chuyến bay hôm nay, tỷ lệ lấp đầy ghế ngồi, 6 đơn đặt vé gần nhất và các yêu cầu cập nhật hộ chiếu cần can thiệp.
- **Trang Dashboard điều hành hiện đại:** [`resources/views/admin/dashboard.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/dashboard.blade.php)
  - **Banner cảnh báo ưu tiên cao:** Tự động xuất hiện cảnh báo khi có yêu cầu cập nhật hộ chiếu đang chờ phê duyệt kèm liên kết kiểm tra nhanh.
  - **Lưới thẻ KPI vận hành:** 4 thẻ chỉ số trực quan có icon và định dạng tiền tệ Việt Nam.
  - **Thanh thao tác nhanh:** Tạo mới chuyến bay, tra cứu đơn vé, quản lý hãng bay, sân bay, gói hành lý.
  - **Biểu đồ Chart.js:** Doanh thu 12 tháng dạng Bar Chart với tooltip VNĐ, biểu đồ Donut tỷ lệ lấp đầy ghế, biểu đồ phân bổ chuyến bay theo từng hãng.
  - **Bảng giao dịch gần đây:** Hiển thị mã đơn `#BK-xxxxx`, tên & avatar khách hàng, chặng bay (`SGN → HAN`), tổng tiền và badge trạng thái.
- **Layout & Topbar Admin:** [`resources/views/layouts/admin.blade.php`](file:///d:/PHP/booking-flight/resources/views/layouts/admin.blade.php)
  - Bổ sung nút **"Xem website"** (`target="_blank"`) giúp quản trị viên mở nhanh trang khách hàng.
  - Nút chuông thông báo hiển thị số lượng yêu cầu hộ chiếu chờ xử lý theo thời gian thực.
  - Thêm mục **"08 Gói hành lý"** vào thanh điều hướng Sidebar Admin.
- **Chuẩn hóa các trang Index:** Đồng bộ giao diện card bóng mờ, typography và nhóm nút thao tác cho toàn bộ các trang:
  - [`admin/flights/index.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/flights/index.blade.php) (Quản lý Chuyến bay)
  - [`admin/bookings/index.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/bookings/index.blade.php) (Quản lý Đơn đặt vé)
  - [`admin/airlines/index.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airlines/index.blade.php) (Quản lý Hãng hàng không)
  - [`admin/airports/index.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airports/index.blade.php) (Quản lý Sân bay)
  - [`admin/aircrafts/index.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/aircrafts/index.blade.php) (Quản lý Máy bay)
  - [`admin/fare-classes/index.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/fare-classes/index.blade.php) (Quản lý Hạng vé)
  - [`admin/baggage-addons/index.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/baggage-addons/index.blade.php) (Quản lý Gói hành lý)
- **Bộ kiểm thử tự động:** Tạo mới [`tests/Feature/Admin/AdminDashboardTest.php`](file:///d:/PHP/booking-flight/tests/Feature/Admin/AdminDashboardTest.php) (4 tests) và cập nhật [`tests/Feature/Admin/AdminSidebarTest.php`](file:///d:/PHP/booking-flight/tests/Feature/Admin/AdminSidebarTest.php) (6 tests).

---

### Hạng mục 6: Khắc phục triệt để lỗi "Chữ tối trên nền tối" (Độ tương phản thấp)
*(Giải quyết yêu cầu trực tiếp của người dùng)*

- **Khắc phục lỗi ghi đè CSS gốc:**
  - Trong [`resources/css/base.css`](file:///d:/PHP/booking-flight/resources/css/base.css), sửa `.bf-body a` thành `.bf-body a:not(.btn):not(.badge) { color: inherit; }`. Điều này ngăn chặn việc ép màu chữ đen `#132434` lên các nút bấm và badge có nền tối.
  - Khai báo quy tắc tương phản cao tường minh cho tất cả các loại button và badge:
    - `a.btn-primary`, `a.btn-danger`, `a.btn-success`, `a.btn-dark`: luôn có `color: #fff !important`.
    - `badge.bg-secondary`, `badge.bg-dark`, `badge.bg-primary`, `badge.bg-danger`: luôn có `color: #fff !important`.
- **Thiết kế lại toàn bộ nút "Quay lại" / "Hủy" trên trang Admin:**
  - Thay thế các nút màu xám xỉn `btn-secondary` bằng nút outline hiện đại `btn btn-outline-secondary` (nền sáng, viền sắc nét, chữ xám đậm rõ ràng `#495057`, khi rê chuột chuyển sang nền xám đậm chữ trắng sắc nét):
    - [`admin/bookings/show.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/bookings/show.blade.php): `<a class="btn btn-outline-secondary shadow-sm"><i class="bi bi-arrow-left"></i> Quay lại danh sách</a>`
    - [`admin/flights/show.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/flights/show.blade.php), [`create.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/flights/create.blade.php), [`edit.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/flights/edit.blade.php)
    - [`admin/airlines/show.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airlines/show.blade.php), [`create.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airlines/create.blade.php), [`edit.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airlines/edit.blade.php)
    - [`admin/airports/show.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airports/show.blade.php), [`create.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airports/create.blade.php), [`edit.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/airports/edit.blade.php)
    - [`admin/aircrafts/show.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/aircrafts/show.blade.php), [`create.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/aircrafts/create.blade.php), [`edit.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/aircrafts/edit.blade.php)
    - [`admin/fare-classes/show.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/fare-classes/show.blade.php), [`create.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/fare-classes/create.blade.php), [`edit.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/fare-classes/edit.blade.php)
    - [`admin/baggage-addons/_form.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/baggage-addons/_form.blade.php) và [`saved-passengers/_form.blade.php`](file:///d:/PHP/booking-flight/resources/views/saved-passengers/_form.blade.php).
- **Thẻ Hỗ trợ kỹ thuật tại Sidebar Admin:**
  - Nối liên kết [`resources/views/layouts/admin.blade.php`](file:///d:/PHP/booking-flight/resources/views/layouts/admin.blade.php): `<a href="mailto:support@jetcharter.test">Mở trung tâm hỗ trợ &rarr;</a>`.
  - Cập nhật [`resources/css/admin.css`](file:///d:/PHP/booking-flight/resources/css/admin.css): chữ vàng sáng `var(--bf-yellow)` (`#ffd34f`) trên nền navy `#103a5b`, khi rê chuột chuyển sang màu trắng `#fff` có gạch chân trực quan.
- **Rà soát toàn bộ Badge trạng thái:**
  - Đảm bảo tất cả badge trong [`admin/bookings/show.blade.php`](file:///d:/PHP/booking-flight/resources/views/admin/bookings/show.blade.php) đều khai báo màu chữ tường minh (`text-white` hoặc `text-dark`).
  - Đổi badge chặng đã chọn trên [`flights/results.blade.php`](file:///d:/PHP/booking-flight/resources/views/flights/results.blade.php) từ màu xám tối sang `badge bg-primary text-white`.
  - Bổ sung `color: #ffffff !important` cho các trạng thái hover của nút chọn chuyến, nút gửi thông tin hành khách và thanh toán trong [`resources/css/flights.css`](file:///d:/PHP/booking-flight/resources/css/flights.css).
  - Bổ sung `color: #fff` tường minh cho `.bf-hero` trong [`resources/css/home.css`](file:///d:/PHP/booking-flight/resources/css/home.css).

---

## 3. Bảng đối chiếu tiến độ: Trước vs Sau

| Vấn đề ghi nhận ở tài liệu cũ | Trạng thái trước đây | Giải pháp đã hoàn thành | File ảnh hưởng chính |
|---|---|---|---|
| **Sơ đồ chọn ghế (Seat Map)** | Có view/JS/CSS nhưng chưa có route/controller, gọi route ảo gây lỗi | Hoàn thiện `SeatSelectionController`, `BookingCreationService`, API lấy sơ đồ ghế, lưu giữ ghế | `SeatSelectionController.php`, `BookingCreationService.php`, `web.php` |
| **Phí chọn ghế** | `seat_selection_fee` có trong DB nhưng không được tính vào tổng tiền | Tự động tính phí khi khách chọn ghế (`is_self_selected`), giữ nguyên giá khi tự động gán | `BookingCreationService.php`, `SeatSelectionController.php` |
| **Cờ ghế tự chọn** | `tickets.is_self_selected` bị hardcode `true` trong seeder | Gán `true` khi khách tự chọn, gán `false` khi hệ thống tự gán; fix `TicketSeeder` | `BookingCreationService.php`, `TicketSeeder.php` |
| **Dashboard khách hàng** | Dữ liệu vé, voucher, số liệu thưởng là hardcode mẫu | Tạo `UserDashboardController`, tải booking thật của user, tính trạng thái upcoming/completed | `UserDashboardController.php`, `user.blade.php`, `dashboard.js` |
| **Bộ lọc Dashboard** | Lọc client-side trên các thẻ tĩnh | Nối bộ lọc mượt mà trên danh sách booking thật của user | `dashboard.js`, `user.blade.php` |
| **8 liên kết chết trang chủ** | 8 liên kết `href="#"` (thẻ điểm đến, xem tất cả, bài viết) | Nối thẻ điểm đến tự chọn sân bay và cuộn tới `#search-box`; xóa bỏ 100% `href="#"` | `home.blade.php`, `home.js` |
| **Form Newsletter** | `action="#"`, thông báo giả không lưu email | Viết endpoint `POST /newsletter/subscribe`, validate email và lưu nhận bản tin | `NewsletterController.php`, `home.blade.php`, `web.php` |
| **Sidebar Admin thiếu mục** | Thiếu route quản lý gói hành lý | Thêm mục "08 Gói hành lý" vào thanh sidebar admin | `layouts/admin.blade.php`, `AdminSidebarTest.php` |
| **Bộ lọc tìm chuyến bay** | Giao diện lọc tại `/flights/results` bấm không có tác dụng | Xây dựng `flights.js`: lọc theo hãng, bay thẳng, 4 khung giờ cất cánh, sort giá/giờ, đếm động, reset | `flights.js`, `results.blade.php`, `flights.css` |
| **Dashboard Admin trên SQLite** | Dùng `MONTH()` của MySQL gây crash test trên SQLite | Dùng biểu thức tính tháng theo driver DB (`strftime` / `MONTH`), bổ sung đầy đủ KPI | `DashboardController.php`, `dashboard.blade.php` |
| **Giao diện bảng biểu Admin** | Bảng mặc định thiếu chỉ báo, thiếu nút điều hướng website | Nâng cấp toàn diện 7 trang Index, thêm nút "Xem website", chuông thông báo hồ sơ | Toàn bộ thư mục `resources/views/admin/` |
| **Lỗi chữ tối trên nền tối** | Nút `btn-secondary` bị ép chữ đen trên nền xám tối, badge xám khó đọc | Sửa CSS gốc `base.css`, chuyển các nút sang `btn-outline-secondary`, chuẩn hóa màu badge | `base.css`, `admin.css`, `flights.css`, `show.blade.php` |

---

## 4. Kết quả kiểm tra & Đo lường chất lượng

```text
=============================================================
1. PHPUNIT TEST SUITE
=============================================================
Runtime:       PHP 8.2.12
Configuration: D:\PHP\booking-flight\phpunit.xml

...............................................................  63 / 379 ( 16%)
............................................................... 126 / 379 ( 33%)
............................................................... 189 / 379 ( 49%)
............................................................... 252 / 379 ( 66%)
............................................................... 315 / 379 ( 83%)
............................................................... 378 / 379 ( 99%)
.                                                               379 / 379 (100%)

Time: 00:21.825, Memory: 66.00 MB
OK (379 tests, 1749 assertions)

=============================================================
2. LARAVEL PINT (PSR-12 CODE STYLE)
=============================================================
{"tool":"pint","result":"passed"}

=============================================================
3. VITE FRONTEND ASSET BUILD
=============================================================
✓ 70 modules transformed.
✓ built in 2.70s
- public/build/assets/flights-B7Yk_3Ig.css   30.07 kB
- public/build/assets/flights-IshcALp3.js    2.53 kB
- public/build/assets/admin-TTmtFQR2.css     12.98 kB
- public/build/assets/home-Di7b5A2n.js      10.69 kB
- public/build/assets/seat-map-B-Z7B1aK.js  10.92 kB
=============================================================
```

---

## 5. Kết luận & Đề xuất tiếp theo

Toàn bộ các yêu cầu của **Phase 2**, danh mục tính năng chưa nối của **Phase Next**, cùng các yêu cầu bổ sung về **bộ lọc tìm kiếm chuyến bay**, **nâng cấp giao diện quản trị Admin** và **xóa bỏ chữ tối trên nền tối** đã được giải quyết triệt để, kiểm thử tự động toàn diện và đạt tiêu chuẩn code sạch.

Nhánh `feature/passenger-management-and-booking-addons` hiện đã hoàn toàn **đủ điều kiện để merge vào nhánh chính (`develop`)**.
