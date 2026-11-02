# Rà soát tiến độ Phase 2

Ngày rà soát: 02/11/2026  
Nhánh đang rà soát: `feature/passenger-management-and-booking-addons`  
Merge nhánh `featureLuanLan2` vào nhánh hiện tại: commit `523f32f`.

## Kết luận

Đồ án **chưa đủ điều kiện merge vào `develop`** theo tiêu chí trong ba tài liệu. Nhiều chức năng P1 và luồng mua hành lý đã có, nhưng vẫn còn việc được giao chưa hoàn thành: 8 liên kết chết ở trang chủ, backend sơ đồ ghế chưa có, các bước kiểm thử thủ công chưa được xác nhận, và autofill hiện điền nhiều trường hơn phạm vi được ghi trong tài liệu.

Theo điều kiện đã thống nhất, nhánh hiện tại được giữ nguyên; chưa checkout hoặc merge vào `develop`.

Danh mục bổ sung về chức năng có giao diện nhưng chưa nối vào dữ liệu thật, bộ lọc mẫu và đề xuất ưu tiên Phase tiếp theo nằm trong [PHASE-NEXT-UNUSED-FEATURES.md](PHASE-NEXT-UNUSED-FEATURES.md).

## Đã hoàn thành trong code

### Namv (`me_2.md`)

- Tìm kiếm, đặt vé khứ hồi và đa chặng; thứ tự chặng, giữ ghế theo transaction, xử lý hết ghế, hết hạn và lịch sử nhiều chặng đã có test.
- Kiểm tra giá vé em bé theo giá ghế gốc của người lớn đi kèm.
- Các bài test luồng một chiều, khứ hồi và đa chặng đều pass trong lần chạy toàn bộ suite.

### Luân (`Luan_2.md` và phần được đưa vào từ `featureLuanLan2`)

- Admin xem danh sách booking, tìm theo mã/tên/email/mã vé, lọc trạng thái, phân trang và xem chi tiết; không có route ghi booking. Có test phân quyền, dữ liệu soft-delete, truy vấn và nội dung chi tiết.
- Sidebar admin đã nối tới các trang quản trị đang có; test sidebar pass.
- Khách chọn gói hành lý cho người lớn/trẻ em; em bé không có gói. Backend lưu gói vào vé, tính giá theo từng chặng và giữ giá ghế gốc để tính giá vé em bé. Lịch sử hiển thị gói. Có bộ test `BaggageBookingTest`.
- Luồng yêu cầu cập nhật hộ chiếu sau khi booking quốc tế đã thanh toán và trước giờ bay, admin duyệt/từ chối, có kiểm tra quyền và trạng thái. Có migration và test cho các luồng này.

### Nguyên (`Nguyen_2.md`)

- Admin quản lý gói hành lý: route đủ 6 endpoint, tìm kiếm, phân trang, tạo/sửa/xóa mềm và test.
- API hồ sơ hành khách đã lưu chỉ trả hồ sơ của user đang đăng nhập; có test quyền, cấu trúc JSON và định dạng ngày.
- Các link trong `layouts/app.blade.php`, `dashboard/user.blade.php` và `auth/register.blade.php` đã được nối hoặc bỏ.
- Autofill có API URL lấy từ Blade, lọc hồ sơ theo loại hành khách và chỉ chạy sau sự kiện chọn; Vite build thành công.

## Chưa hoàn thành hoặc cần xử lý

### 1. Tám liên kết chết ở trang chủ

`me_2.md` giao Namv nối 8 liên kết `href="#"` trong `resources/views/home.blade.php`. Hiện vẫn còn tại các dòng 327, 331, 336, 341, 346, 351, 396 và 402: mục “Xem tất cả”, năm thẻ điểm đến và hai liên kết bài viết.

Ở lần rà soát thứ hai, mình cũng xác nhận `home.blade.php` có form nhận email với `action="#"`; `home.js` chỉ hiện thông báo mẫu, không lưu địa chỉ. Đây không phải thẻ `href`, nhưng vẫn là tương tác chưa có backend.

**Đề xuất xử lý link trang chủ, không cần tạo trang mới:**

- Năm thẻ điểm đến: trỏ về `#search-box`, kèm mã sân bay đích; JavaScript chọn sân bay tương ứng trong picker hiện có rồi cuộn tới form. Khách tự chọn điểm đi và ngày bay. Như vậy thẻ thực sự giúp bắt đầu tìm chuyến và không cần hardcode ngày/giá vé.
- “Xem tất cả”: hiện chưa có trang danh mục điểm đến. Nên bỏ thẻ liên kết theo hướng dẫn task, hoặc đổi thành “Tìm chuyến bay” trỏ tới `#search-box`.
- Hai CTA “Đọc bài viết”: chưa có route/trang bài viết. Nên bỏ thẻ `<a>` và giữ phần chữ như nội dung không tương tác cho tới khi có trang bài viết thật.
- Newsletter: chỉ nên giữ form đăng ký nếu có endpoint lưu email; nếu chưa làm backend thì đánh dấu rõ là demo hoặc tạm bỏ form để tránh tạo cảm giác email đã được đăng ký.

### 2. Sidebar admin chưa có link gói hành lý

Route `admin.baggage-addons.index` đã tồn tại nhưng `resources/views/layouts/admin.blade.php` chưa có mục “Gói hành lý”. `AdminSidebarTest` hiện chỉ kỳ vọng 7 mục và không kiểm tra route này. Do đó quản trị viên phải tự nhập URL `/admin/baggage-addons` để mở trang. Tài liệu của Luân ghi rõ mục này cần được thêm sau khi route của Nguyên có mặt; điều kiện đó hiện đã đạt.

### 3. Seat map chưa có backend và chưa nối vào luồng đặt vé

- Không có `UI-GUIDE-SeatMap.md` trong workspace.
- Không tìm thấy route/controller cung cấp JSON sơ đồ ghế hoặc nhận lựa chọn/giữ ghế.
- `resources/views/booking/seats.blade.php` đang gọi route `booking.seats.store`, nhưng route này không tồn tại. View cũng cần `$mapUrls` và dữ liệu ghế mà controller hiện chưa truyền.
- `BookingController::store()` hiện vẫn tự chọn ghế và đi thẳng tới thanh toán; chưa xử lý ghế khách chọn, phí chọn ghế hay trạng thái `is_self_selected`.
- Vì vậy, các file Blade/JS/CSS sơ đồ ghế có trong nhánh chưa tạo thành chức năng có thể truy cập và sử dụng.

Đây là phần P3 trong `me_2.md` và P3 giao diện trong `Luan_2.md`; backend là điều kiện cần trước khi UI hoàn thành.

### 4. Kiểm thử thủ công chưa được xác nhận

Chưa có kết quả checklist desktop/mobile của:

- Luồng booking một chiều, khứ hồi, đa chặng từ tìm kiếm đến lịch sử.
- Admin: sidebar, CRUD, danh sách/chi tiết booking và quyền user thường.
- Tài khoản: xác thực, hồ sơ, hồ sơ hành khách và autofill.

Test tự động không thay thế các bước thủ công mà ba tài liệu yêu cầu.

### 5. Autofill vượt phạm vi ghi trong tài liệu

`Nguyen_2.md` yêu cầu chỉ tự điền `full_name`, `document_number`, `date_of_birth`. Hiện `resources/js/passenger-autofill.js` còn điền giới tính, quốc tịch, nước cấp, ngày cấp và ngày hết hạn khi là chuyến quốc tế. Cần thống nhất yêu cầu hiện hành hoặc giới hạn lại hành vi trước khi nghiệm thu P2.

### 6. Migration cho yêu cầu cập nhật hộ chiếu

Migration `2026_10_18_100000_create_passport_update_requests_table.php` có trong code. Môi trường database muốn chạy thủ công tính năng này cần chạy `php artisan migrate` trước.

### 7. Payment sandbox

`me_2.md` ghi mục payment sandbox đang tạm hoãn, chưa giao. Không tính đây là lỗi merge của Phase 2; nếu đồ án yêu cầu thanh toán sandbox thì cần quyết định phạm vi riêng.

## Kết quả kiểm tra tự động

- `php artisan test`: **360 tests passed, 1.586 assertions**.
- `vendor/bin/pint --test`: **passed**. Có 8 file PHP được Pint chuẩn hóa dòng trống cuối file trong lúc rà soát.
- `npm run build`: **passed**. Vite còn cảnh báo `/images/hero-banner.jpg` không resolve lúc build và để tham chiếu xử lý runtime.
- `php artisan route:list --name=baggage`: đủ 6 route.
- `php artisan route:list --name=admin.bookings`: đủ 2 route index/show.
- Các route cập nhật hộ chiếu tồn tại.
- Các file user-facing được Nguyên giao không còn `href="#"`; trang chủ còn 8 như trên.
- Sơ đồ ghế không có route `booking.seats.store`.

## Trạng thái Git tại lúc lập báo cáo

- Đang ở `feature/passenger-management-and-booking-addons`; merge commit `523f32f` đã kết hợp nội dung `featureLuanLan2` vào nhánh này.
- Chưa push và chưa merge `develop`.
- Working tree còn các thay đổi seeder từ yêu cầu chuyến bay 30/11: `FlightSeatSeeder.php`, `TicketSeeder.php` và file mới `November30FlightSeeder.php`.
- Việc chuẩn hóa Pint ở 8 file PHP nêu trên cũng đang là thay đổi chưa commit.

## Việc cần hoàn tất trước khi xem xét merge `develop`

1. Nối hoặc bỏ 8 `href="#"` trong `home.blade.php` theo yêu cầu Namv.
2. Thêm link `Gói hành lý` vào sidebar admin và cập nhật test để kiểm tra mục này.
3. Hoặc hoàn thiện form newsletter phía server, hoặc trình bày rõ đây chỉ là demo và không nhận email thật.
4. Hoàn thiện hợp đồng và backend seat map (JSON, lấy/giữ ghế, hết hạn, phí, transaction, nhiều chặng), rồi nối route/controller và luồng UI; bảo đảm `booking.seats.store` tồn tại.
5. Bổ sung/đối chiếu test cho seat map backend và xác nhận giao diện hoạt động.
6. Quyết định phạm vi autofill các trường hộ chiếu và cập nhật code/tài liệu cho thống nhất.
7. Chạy checklist thủ công desktop/mobile của Namv, Luân và Nguyên; lưu kết quả/lỗi.
8. Xem xét, commit các thay đổi seeder/Pint đang còn trong working tree.
9. Sau khi các mục trên xong, chạy lại test, Pint, build và mới đánh giá merge vào `develop`.
