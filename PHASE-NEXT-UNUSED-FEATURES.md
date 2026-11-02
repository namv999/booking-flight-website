# Danh mục chức năng hiện có nhưng chưa dùng đầy đủ

Ngày rà soát: 02/11/2026  
Nhánh: `feature/passenger-management-and-booking-addons`  
Mục đích: kiểm kê phần giao diện, dữ liệu mẫu, cấu hình và bộ lọc chưa nối vào luồng thật để chọn phạm vi Phase tiếp theo.

## Tóm tắt

Các chức năng chính của luồng tìm chuyến, đặt vé khứ hồi/đa chặng, mua hành lý, lịch sử booking, hồ sơ hành khách, cập nhật hộ chiếu và quản trị booking đã có code và test tự động. Những khoảng trống lớn còn lại là:

1. Chọn ghế: có một bộ giao diện JS/Blade/CSS nhưng không có backend route/controller, nên chưa truy cập/dùng được.
2. Dashboard khách: số liệu và chuyến bay là nội dung mẫu; tab lọc chỉ ẩn/hiện các thẻ mẫu.
3. Trang chủ: điểm đến, ưu đãi, bài viết, giá quảng cáo và metrics là dữ liệu tĩnh; form newsletter chỉ giả lập thành công.
4. Tám liên kết trang chủ vẫn là `href="#"`; sidebar admin chưa có link tới quản lý gói hành lý.
5. Phí chọn ghế và cờ `is_self_selected` đã có trong schema/UI nhưng chưa được nghiệp vụ đặt vé sử dụng.

## A. Có giao diện/mã nguồn nhưng chưa nối vào luồng thật

| Mục | Hiện trạng | Tác động | Đề xuất cho Phase kế tiếp |
|---|---|---|---|
| Sơ đồ chọn ghế | Có `booking/seats.blade.php`, `seat-map.js`, `seat-map.css`; view yêu cầu `$mapUrls`, `$oldSeats`, dữ liệu hành khách và form gọi `booking.seats.store`. Không có route/controller cung cấp các dữ liệu hoặc nhận lựa chọn. | Không thể mở trang qua luồng đặt vé; render view trực tiếp cũng lỗi route/data. | Hoàn thành backend API và bước booking trước: lấy map ghế, trạng thái lazy expiry, giữ/đổi ghế atomic, phí, nhiều chặng; sau đó nối UI. |
| Phí chọn ghế | `fare_classes.seat_selection_fee` có migration, CRUD và hiển thị ở admin. `BookingController` hiện tự chọn ghế và không tính trường phí này. | Cấu hình admin chưa ảnh hưởng giá vé. | Dùng phí chỉ khi khách tự chọn ghế; ghi snapshot vào vé/tổng tiền và test race condition, nhiều chặng. |
| Cờ ghế khách tự chọn | `tickets.is_self_selected` có trong schema và lịch sử hiển thị badge khi `true`; luồng đặt vé hiện tại không nhận lựa chọn ghế và mặc định dùng giá trị DB. | Chưa phản ánh được ý nghĩa thật của cờ. TicketSeeder còn tạo vé người lớn với `true`, dù vé được seed tự động. | Sau khi seat map hoạt động, ghi `true` chỉ cho ghế khách chọn; sửa dữ liệu demo/Seeder để tự động gán ghế là `false`. |
| Dashboard khách | `dashboard/user.blade.php` dùng số liệu thưởng, voucher, chuyến bay và mã booking mẫu. Route dashboard trả view, không truyền booking thật. | Khách thấy dữ liệu không phải của mình; chức năng “chuyến đi của tôi” không đại diện lịch sử thật. | Tạo controller/query booking của chính user, tính trạng thái upcoming/completed theo thời gian và hiển thị dữ liệu thật. |
| Bộ lọc dashboard khách | `dashboard.js` lọc thẻ theo `data-trip-status`; hiện chỉ hoạt động trên các thẻ mẫu trong Blade. | Nút “Sắp tới/Đã hoàn thành” có hiệu ứng nhưng kết quả không dựa trên booking thật. | Giữ bộ lọc phía client sau khi render booking thật, hoặc chuyển thành query filter có pagination nếu danh sách lớn. |
| Điểm đến trang chủ | Năm thẻ địa điểm viết cứng trong `home.blade.php`; `HomeController` không truyền collection điểm đến. Tám anchor chưa điều hướng. | Dữ liệu có thể lệch với sân bay/chuyến bay đang có; click không làm gì. | Dùng sân bay có route/chuyến bay; khi click chọn sẵn điểm đến trong picker tìm chuyến và cuộn tới form. |
| Giá và ưu đãi trang chủ | Các mức “Từ …đ”, nội dung giảm 15%, mã `FLY15`, metrics 24/7–120+–15% là hardcode; chưa thấy logic áp dụng coupon. | Nội dung có thể không khớp giá/thời gian hay số liệu thật; mã ưu đãi không làm thay đổi giá. | Hoặc đánh dấu là nội dung demo, hoặc làm bảng/chính sách khuyến mãi và validate/tính giảm ở server trước khi giữ chỗ. |
| Bài viết trang chủ | Hai bài viết tĩnh, CTA “Đọc bài viết” là `href="#"`; không có route/trang chi tiết. | CTA không mở nội dung. | Nếu chưa có CMS/bài viết thì bỏ anchor; nếu cần nội dung, thêm route detail và nguồn dữ liệu rõ ràng. |
| Newsletter | Form `action="#"`; `home.js` chặn submit rồi hiển thị “Email đã được ghi nhận” mà không gửi/lưu email. | Người dùng nhận thông báo thành công giả. | Thêm endpoint lưu đăng ký và xác nhận kết quả thật; nếu chưa làm thì thay thông báo bằng trạng thái demo hoặc ẩn form. |
| Nút thông báo admin | Nút chuông trong `layouts/admin.blade.php` không có handler/route được thấy trong mã rà soát. | Nút trông như thao tác nhưng không mở thông báo. | Thêm trung tâm thông báo nếu nằm trong scope; nếu không thì bỏ nút hoặc hiển thị disabled kèm lý do. |
| Hỗ trợ admin | Sidebar hiện dòng “Mở trung tâm hỗ trợ” dạng chữ tĩnh, không phải link; không có route hỗ trợ được thấy. | Không điều hướng tới hỗ trợ. | Giữ chữ như nội dung tĩnh hoặc nối tới kênh hỗ trợ thật khi có đích. |
| Link quản lý gói hành lý admin | Route `admin.baggage-addons.index` có thật nhưng chưa có mục tương ứng trong sidebar admin. | Chức năng có thể dùng bằng URL trực tiếp, nhưng admin khó khám phá. | Thêm link và test sidebar sau mục Hạng vé. |

## B. Các bộ lọc: đang dùng và chưa dùng

### Đang nối với dữ liệu thật

- Trang admin hãng bay: tìm theo tên/mã, phân trang.
- Trang admin máy bay: tìm theo model/registration, phân trang.
- Trang admin sân bay: tìm theo tên/thành phố/mã, phân trang.
- Trang admin hạng vé: tìm theo tên, phân trang.
- Trang admin chuyến bay: tìm theo sân bay đi/đến, phân trang.
- Trang admin gói hành lý: tìm theo tên, phân trang.
- Admin booking: tìm theo mã booking/tên/email/mã vé, lọc `pending`/`paid`/`cancelled`, phân trang và giữ query khi chuyển trang.
- Tìm chuyến phía khách: điểm đi/đến, ngày, hạng vé và loại hành trình được validate phía server; service lọc chuyến và ghế còn khả dụng.

### Có UI nhưng mới lọc dữ liệu mẫu

- Dashboard khách có tab “Sắp tới” và “Đã hoàn thành”; JavaScript hoạt động nhưng các booking là hardcode, nên chưa phải bộ lọc booking của tài khoản.

### Chưa có trong giao diện hiện tại

- Lịch sử booking khách hiện phân trang nhưng chưa có ô tìm kiếm hoặc lọc trạng thái. Chỉ thêm nếu Phase tiếp theo yêu cầu; tránh tạo thêm bộ lọc trùng với chức năng hiện tại.
- Danh sách admin chuyến bay đang có tìm kiếm theo sân bay, nhưng chưa thấy lọc trạng thái `scheduled`/`delayed`/`cancelled`; cân nhắc nếu admin cần tra cứu lịch bay theo trạng thái.

## C. Chức năng đang dùng thật — không nên xếp vào “unused”

- Bộ lọc admin booking theo trạng thái và tìm kiếm.
- Tìm kiếm chuyến bay một chiều, khứ hồi, đa chặng.
- CRUD và tìm kiếm các bảng quản trị, bao gồm gói hành lý.
- Chọn hành lý mua thêm ở form booking và tính phí theo mỗi chặng.
- Hồ sơ hành khách đã lưu, endpoint JSON giới hạn theo user và autofill.
- Gửi yêu cầu cập nhật hộ chiếu và admin duyệt/từ chối.
- Dashboard admin tính ghế, tỷ lệ lấp đầy, doanh thu theo tháng và số chuyến theo hãng. Lưu ý trang này dùng `MONTH()` của MySQL nên test SQLite hiện không render được theo ghi chú trong test; cần xác nhận trên MySQL thực tế.

## D. Các việc cần chốt khi lập Phase tiếp theo

1. Ưu tiên backend chọn ghế hay hoàn thiện các trang dữ liệu mẫu trước.
2. Chốt dữ liệu nào cần lấy từ database: dashboard khách, điểm đến, ưu đãi, bài viết, newsletter.
3. Quyết định có triển khai coupon `FLY15` hay bỏ khỏi giao diện.
4. Bổ sung link gói hành lý vào sidebar admin; xử lý nút thông báo admin.
5. Quyết định có thêm bộ lọc trạng thái chuyến bay và lọc lịch sử booking không.
6. Rà các seed/demo: không để nhãn “Ghế tự chọn” xuất hiện với vé được tự động gán.
7. Kiểm thử thủ công trên desktop/mobile sau khi nối backend; test tự động hiện tại không xác nhận các thao tác JS/browser này.

## Trạng thái kiểm tra gần nhất

Theo lượt rà soát trước: `php artisan test` 360/360 pass, `vendor/bin/pint --test` pass, `npm run build` pass. Đây là kiểm tra code hiện tại, không chứng minh các phần mock/UI-only trong bảng đã hoạt động end-to-end.
