# Cách chạy bản source đã gắn code của Luân

Bản này = `develop` + P1-A (admin xem đơn đặt vé) + P1-B (sidebar admin) + P2 (gói hành lý khi đặt vé)
+ P-B (cập nhật hộ chiếu sau đặt vé) + P3 (giao diện sơ đồ ghế, CHƯA nối vào luồng thật).

## Yêu cầu
- PHP 8.2 trở lên (XAMPP 8.2.x là được), bật extension `pdo_mysql` (mặc định có) và `pdo_sqlite` (chỉ cần khi chạy test)
- Composer
- MySQL/MariaDB (bật MySQL trong XAMPP Control Panel)
- Node.js: KHÔNG bắt buộc, vì thư mục `public/build` đã build sẵn. Chỉ cần khi sửa CSS/JS.

## Các bước (mở CMD/PowerShell tại thư mục này)
1. `composer install`
2. `copy .env.example .env` (Mac/Linux: `cp .env.example .env`), rồi `php artisan key:generate`
3. Tạo database (hoặc dùng phpMyAdmin tạo `booking_flight`, kiểu `utf8mb4_unicode_ci`):
   `C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE booking_flight CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"`
4. `php artisan migrate:fresh --seed`
   (XÓA SẠCH dữ liệu cũ trong database `booking_flight` rồi nạp dữ liệu mẫu mới. Có migration mới
   `passport_update_requests`. Nếu muốn giữ dữ liệu cũ thì chỉ chạy `php artisan migrate`.)
5. `php artisan serve` rồi mở http://127.0.0.1:8000

Nếu đã có file `.env` đang chạy ổn ở máy bạn thì giữ nguyên, chỉ cần đảm bảo `SESSION_DRIVER=file`.

## Tài khoản mẫu (mật khẩu `password`)
- Admin: `admin@bookingflight.test`  → vào http://127.0.0.1:8000/admin/dashboard
- Khách: `user@bookingflight.test`

## Xem từng phần ở đâu
- P1-A: `/admin/bookings` (tìm theo mã đơn, tên, email, mã vé; lọc trạng thái), bấm "Xem" để vào chi tiết đơn
- P1-B: sidebar admin, mọi mục đều là link thật, mục đang mở được tô sáng
- P2: đặt vé (tìm chuyến, chọn chuyến, tới form "Thông tin hành khách"): mỗi người lớn/trẻ em có ô "Hành lý mua thêm"
  (em bé không có). Giá gói cộng vào vé từng chặng. Xem lại ở "Chuyến đi của tôi" (lịch sử đặt vé)
- P-B: khách vào chi tiết một đơn QUỐC TẾ, ĐÃ THANH TOÁN, chuyến đầu CHƯA bay → mục "Cập nhật hộ chiếu".
  Admin vào `/admin/bookings/{id}` → thẻ "Yêu cầu cập nhật hộ chiếu" để Duyệt hoặc Từ chối
- P3: file `resources/views/booking/seats.blade.php`, `resources/js/seat-map.js`, `resources/css/seat-map.css`.
  Chưa truy cập được vì backend sơ đồ ghế của Namv chưa có (xem "Chưa nối" bên dưới)

## Chạy test
`php artisan test` (dùng SQLite trong bộ nhớ, không đụng database thật). Kỳ vọng 335 test pass.
`vendor/bin/pint --test` để kiểm tra style.

## Nếu sửa CSS/JS
`npm install` rồi `npm run build` (hoặc `npm run dev` khi đang phát triển).
Nếu trang báo lỗi "Vite manifest not found" nghĩa là thiếu `public/build`: chạy `npm install && npm run build`.

## Chưa nối / cần biết
1. **Trang chọn ghế (P3) chưa nối vào luồng.** Form hành khách vẫn gửi tới `booking.passengers.store`. Khi Namv báo backend
   seat map đã merge, đổi `action` của form trong `booking/passengers.blade.php` sang `route('booking.passengers.next')`
   (theo UI-GUIDE-SeatMap.md mục 8).
2. Thanh tiến trình đã là 6 bước (có "Chọn ghế") nhưng luồng hiện tại chưa đi qua bước 4, nên tạm thời bước này bị bỏ qua.
3. Route `baggage-addons` của admin (Nguyên làm ở commit 2b4dcbd) đã mất khỏi `routes/web.php`, nên sidebar chưa có mục "Gói hành lý".
   Khi route được khôi phục, thêm một dòng link `route('admin.baggage-addons.index')` vào sidebar.
4. Giờ bay: trang admin chi tiết đơn và trang chọn ghế hiển thị theo múi giờ từng sân bay (có ghi UTC+x), trong khi các trang
   khách khác của dự án đang hiển thị giờ UTC thô.
