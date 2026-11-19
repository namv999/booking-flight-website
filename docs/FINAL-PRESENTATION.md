# BÁO CÁO THUYẾT TRÌNH: WEBSITE ĐẶT VÉ MÁY BAY

Thời lượng gợi ý khoảng 18 phút, gồm 21 slide và phần demo 5 phút. Mỗi slide gồm: Nội dung (chữ trên slide), Chèn ảnh, Demo (nếu có)

---

## Slide 1: Trang bìa (0,5')

**Nội dung**
- Tiêu đề: **Website đặt vé máy bay**
- Phụ đề: Đặt vé nhiều chặng, chọn ghế và giữ ghế an toàn
- Môn học, giảng viên hướng dẫn, nhóm 4 thành viên, ngày báo cáo

---

## Slide 2: Nội dung trình bày (0,5')

**Nội dung**
1. Giới thiệu đề tài và lý do chọn
2. Phân tích và thiết kế hệ thống
3. Giải pháp cho bài toán giữ ghế
4. Chức năng chính và demo
5. Kiểm thử, hạn chế, hướng phát triển

---

## Slide 3: Giới thiệu đề tài và lý do chọn (1,5')

**Nội dung**
Đề tài xây dựng website cho phép khách hàng tìm chuyến bay, đặt vé một chiều, khứ hồi hoặc nhiều chặng, chọn ghế, mua thêm hành lý, thanh toán và nhận vé điện tử. Quản trị viên quản lý dữ liệu khai thác và theo dõi doanh thu.

Nhóm chọn đề tài vì ba lý do. Thứ nhất, đây là bài toán thực tế mà người dùng nào cũng quen thuộc. Thứ hai, nó chứa những vấn đề kỹ thuật điển hình: nhiều người tranh cùng một ghế, giao dịch dữ liệu phải toàn vẹn, hành trình nhiều chặng. Thứ ba, đề tài bao phủ đủ các yêu cầu của môn học: phân quyền, CRUD, tìm kiếm, phân trang, thống kê, gửi email và xuất PDF.

**Chèn ảnh:**

---

## Slide 4: Mục tiêu và phạm vi (1')

**Nội dung**
Hệ thống hướng tới bốn mục tiêu: đặt được vé một chiều, khứ hồi và đa chặng; cho khách tự chọn ghế và mua thêm hành lý; giữ ghế tạm thời để tránh đặt trùng; và cho quản trị viên quản lý dữ liệu, xem thống kê.

Thanh toán được mô phỏng (thành công hoặc thất bại) vì đề bài không yêu cầu cổng thật. Hệ thống không làm thanh toán qua VNPay/Momo, chat real-time hay chatbot.

---

## Slide 5: Công nghệ và quyết định kiến trúc (1')

**Nội dung**

| Thành phần | Công nghệ |
|---|---|
| Backend | Laravel (monolith), Eloquent, Form Request |
| Giao diện | Blade, Bootstrap 5, JavaScript thuần (fetch) |
| Đăng nhập, phân quyền | Laravel Breeze, middleware `auth` và `admin` |
| Cơ sở dữ liệu | MariaDB/MySQL (InnoDB) |
| Tiện ích | Chart.js, dompdf, Laravel Mail |

Nhóm giữ kiến trúc monolith Blade thay vì tách SPA và REST API. Cách này tránh các rủi ro về CORS, token và quản lý trạng thái phía client, phù hợp với năng lực và thời gian của nhóm.

**Chèn ảnh:** logo công nghệ (Laravel, Bootstrap, MariaDB, Chart.js).

---

## Slide 6: Phân tích yêu cầu: tác nhân và use case (1,5')

**Nội dung**
Hệ thống có hai tác nhân. Khách hàng có 7 chức năng: đăng ký, đăng nhập, tìm chuyến, đặt vé, xem lịch sử đặt vé, quản lý hồ sơ hành khách đã lưu. Quản trị viên có 5 chức năng: quản lý danh mục, quản lý chuyến bay, quản lý gói hành lý, xem đơn đặt vé, xem thống kê doanh thu.

Quan hệ giữa các use case: Chọn ghế, Mua gói hành lý và Điền nhanh từ hồ sơ là các mở rộng tùy chọn của Đặt vé (`«extend»`). Đặt vé bắt buộc có Thanh toán, và Thanh toán tự động gửi email xác nhận (`«include»`).

**Chèn ảnh:** Sơ đồ use case, slide riêng.

---

## Slide 7: Kiến trúc hệ thống (1')

**Nội dung**
Một yêu cầu đi từ trình duyệt qua Route và Middleware, vào Controller. Controller dùng Form Request để kiểm tra dữ liệu, giao nghiệp vụ cho Service (đặt vé, hết hạn, tìm chuyến, rủi ro hộ chiếu), rồi đọc ghi dữ liệu qua Model Eloquent xuống MariaDB. Mail và dompdf tạo và gửi vé điện tử.

Controller giữ mỏng, nghiệp vụ nằm ở Service nên dễ kiểm thử và dễ bảo trì.

**Chèn ảnh:** Sơ đồ component.

---

## Slide 8: Thiết kế cơ sở dữ liệu (1,5')

**Nội dung**
Cơ sở dữ liệu gồm 16 bảng nghiệp vụ. Một số quyết định thiết kế chính:

- Tách `seats` (ghế vật lý của máy bay) và `flight_seats` (ghế trên từng chuyến, mang giá và trạng thái), để cùng một máy bay khai thác nhiều chuyến.
- Bảng `booking_flights` nối một booking với nhiều chuyến, nhờ đó hỗ trợ khứ hồi và đa chặng.
- `tickets.price` lưu giá tại thời điểm đặt, nên đổi giá sau này không ảnh hưởng vé cũ.
- 5 bảng danh mục (hãng bay, máy bay, sân bay, hạng vé, gói hành lý) dùng Soft Delete để giữ lịch sử.

**Chèn ảnh:** ERD

---

## Slide 9: Quy tắc nghiệp vụ cốt lõi (1,5')

**Nội dung**
- **Giá vé** của mỗi người = giá ghế + phí chọn ghế (chỉ khi tự chọn ghế) + gói hành lý.
- **Tổng đơn** = tổng giá vé của mọi chặng; gói hành lý áp dụng và tính tiền cho từng chặng.
- **Em bé** không chiếm ghế, giá bằng 10% giá ghế gốc của người lớn đi kèm, không mua hành lý.
- **Hạng vé** dùng chung cho mọi chặng; thứ tự chặng suy ra từ giờ khởi hành.
- **Chuyến quốc tế** yêu cầu thông tin hộ chiếu. Nếu hộ chiếu còn dưới 6 tháng, hệ thống cảnh báo và bắt buộc khách tick xác nhận trước khi thanh toán (kiểm tra cả ở phía server).

**Ví dụ minh họa** (khứ hồi, 1 người lớn + 1 em bé, giá ghế 1.000.000đ mỗi chặng, gói hành lý 150.000đ, phí chọn ghế 60.000đ):
- Người lớn: (1.000.000 + 150.000 + 60.000) × 2 = 2.420.000đ
- Em bé: 10% × 1.000.000 × 2 = 200.000đ
- Tổng đơn: 2.620.000đ

**Chèn ảnh:** bảng ví dụ trên dưới dạng bảng tính nhỏ.

---

## Slide 10: Luồng đặt vé tổng thể (1')

**Nội dung**
Khách thực hiện năm bước: (1) tìm chuyến ngay ở trang chủ; (2) chọn chuyến cho từng chặng; (3) nhập thông tin hành khách, có thể điền nhanh từ hồ sơ đã lưu và chọn gói hành lý; (4) chọn ghế cho từng chặng hoặc để hệ thống tự gán; (5) thanh toán, sau đó nhận vé PDF và email xác nhận.

Khi tìm theo ngày, hệ thống đổi sang khoảng giờ UTC theo múi giờ của sân bay đi từng chặng, nên kết quả đúng với giờ địa phương.

**Chèn ảnh:**

---

## Slide 11: Bài toán giữ ghế và chống đặt trùng (2')

**Nội dung**
**Vấn đề:** hai người có thể cùng bấm đặt một ghế, và một khách bỏ dở thanh toán có thể giữ ghế mãi.

**Giải pháp của nhóm:**
- Ghế được giữ 20 phút. Hết hạn, ghế được coi là trống ngay khi truy vấn (lazy expiry), nên không cần queue hay scheduler trên XAMPP/Windows.
- Tạo đơn trong một `DB::transaction` kèm `lockForUpdate`. Ghế được khóa theo thứ tự cố định để tránh deadlock.
- Nếu một chặng thiếu ghế, toàn bộ đơn bị rollback, khách không bao giờ có vé đi mà thiếu vé về.
- Ràng buộc UNIQUE trên `tickets.flight_seat_id` là lớp bảo vệ cuối cùng ở tầng cơ sở dữ liệu.

**Chèn ảnh:** hai sơ đồ trạng thái đặt cạnh nhau:
- Ghế: `available → held → booked`, và `held → available` khi hết hạn hoặc hủy.
- Booking: `pending → paid` hoặc `pending → cancelled`.

---

## Slide 12: Luồng tạo booking khi chọn ghế (1')

**Nội dung**
Khi khách xác nhận ghế, Controller gọi service tạo đơn. Service khóa các ghế liên quan, kiểm tra ghế đúng chuyến, đúng hạng vé và còn trống, rồi đánh dấu `held`, tạo booking pending cùng các vé trong cùng một giao dịch. Nếu đủ ghế thì COMMIT và chuyển khách sang trang thanh toán. Nếu thiếu ghế thì ROLLBACK và báo khách chọn lại.

**Chèn ảnh:** Sơ đồ sequence "tạo booking".

---

## Slide 13: Luồng thanh toán an toàn (1')

**Nội dung**
Khi khách bấm thanh toán, hệ thống khóa booking trước, kiểm tra còn ở trạng thái pending và chưa hết hạn, sau đó khóa các ghế và xác nhận ghế vẫn đang được giữ đúng chủ. Chỉ khi đó booking mới chuyển sang paid và ghế sang booked. Nếu hết hạn hoặc mất ghế, giao dịch bị hủy và khách được báo đặt lại.

Việc gửi email xác nhận được bọc để nếu lỗi SMTP chỉ ghi log, không làm hỏng một thanh toán đã thành công.

**Chèn ảnh:** Sơ đồ sequence "thanh toán".

---

## Slide 14: Tính năng nổi bật phía khách hàng (1')

**Nội dung**
- Tìm chuyến một chiều, khứ hồi, đa chặng ngay tại trang chủ.
- Sơ đồ ghế riêng cho từng chặng; nếu khách không chọn, hệ thống tự gán ghế.
- Gói hành lý: mỗi người chọn tối đa một gói, áp dụng cho mọi chặng.
- Hồ sơ hành khách đã lưu giúp điền nhanh form nhập thông tin.
- Chuyến quốc tế có nhập hộ chiếu, cảnh báo hộ chiếu sắp hết hạn, và cho phép gửi yêu cầu chỉnh sửa hộ chiếu sau khi đặt vé.
- Lịch sử đặt vé, tải vé PDF và nhận email xác nhận.

**Chèn ảnh:** 4 ảnh chụp màn hình: kết quả tìm kiếm khứ hồi; sơ đồ chọn ghế; trang thanh toán có đồng hồ đếm ngược; vé PDF.

---

## Slide 15: Phân hệ quản trị (1')

**Nội dung**
Quản trị viên quản lý hãng bay, máy bay, sân bay, hạng vé, chuyến bay và gói hành lý với đầy đủ tìm kiếm và phân trang. Trang đơn đặt vé cho phép tìm theo mã đơn, tên, email, mã vé và lọc theo trạng thái; trang này chỉ xem, không sửa hay hủy. Bảng điều khiển dùng Chart.js hiển thị doanh thu theo tháng, số chuyến theo hãng và tỷ lệ lấp đầy ghế. Admin cũng duyệt hoặc từ chối các yêu cầu cập nhật hộ chiếu của khách.

**Chèn ảnh:** ảnh chụp dashboard admin và trang danh sách đơn đặt vé.

---

## Slide 16: Bảo mật, phân quyền và chất lượng mã (1')

**Nội dung**
Phân quyền hai vai trò bằng middleware `auth` và `admin`. Mọi thao tác trên booking, hành khách đã lưu đều kiểm tra người dùng là chủ sở hữu. Dữ liệu đầu vào chính đi qua Form Request. Soft Delete giữ lại dữ liệu danh mục cũ để lịch sử đặt vé vẫn hiển thị đúng. Mã nguồn được tổ chức thành các tầng Controller, Service, Model và thống nhất định dạng bằng Pint.

---

## Slide 17: Kiểm thử (1')

**Nội dung**

| Hạng mục | Kết quả |
|---|---|
| Kiểm thử tự động (`php artisan test`) | 399 test đạt |
| Định dạng mã (Pint) | 195 file đạt |
| Build giao diện (`npm run build`) | Thành công |
| Số route | 89 |

Các kịch bản đã kiểm thử gồm: đặt vé một chiều, khứ hồi, đa chặng; rollback khi một chặng hết ghế; hết hạn booking nhả ghế mọi chặng; giá em bé tính theo từng chặng; hành lý; quyền truy cập lịch sử đặt vé. Ngoài ra nhóm kiểm thử thủ công trên máy tính và điện thoại.

**Chèn ảnh:** ảnh chụp kết quả `php artisan test`.

---

## Slide 18: Demo (5')

**Chuẩn bị:** chạy `migrate:fresh --seed`; có 1 tài khoản khách đã xác thực email có sẵn hồ sơ hành khách, 1 tài khoản admin; chuyến đi và chuyến về còn đủ ghế; có ít nhất một chuyến quốc tế; SMTP đã cấu hình.

| Bước | Trang | Thao tác |
|---|---|---|
| 1 | Trang chủ `/` | Chọn khứ hồi, sân bay, ngày, 2 người lớn + 1 em bé, bấm tìm |
| 2 | Kết quả tìm chuyến `/flights/results` và `/booking/leg/{n}` | Chọn chuyến đi, rồi chuyến về |
| 3 | Nhập hành khách `/booking/passengers` | Điền nhanh từ hồ sơ đã lưu, chọn gói hành lý, gắn em bé vào người lớn |
| 4 | Chọn ghế `/booking/seats` | Chọn ghế từng chặng, xem phí chọn ghế |
| 5 | Thanh toán `/payment/{id}` | Xem đồng hồ đếm ngược, thanh toán thành công |
| 6 | Chi tiết đơn `/booking-history/{id}` | Xem đủ hai chặng, tải vé PDF |
| 7 | Hộp thư email | Mở email xác nhận kèm vé PDF |
| 8 | Admin `/admin/dashboard`, `/admin/bookings` | Thấy đơn vừa đặt và biểu đồ doanh thu |

---

## Slide 19: Hạn chế và hướng phát triển (1')

**Nội dung**

| Hạn chế hiện tại | Hướng phát triển |
|---|---|
| Thanh toán mới mô phỏng | Tích hợp cổng thanh toán sandbox |
| Email gửi đồng bộ | Dùng hàng đợi (Queue) |
| Phí ghế theo hạng vé | Phí theo vị trí ghế (cửa sổ, lối đi) |
| Một số validation còn viết inline | Chuyển sang Form Request |
| Chưa có đổi, hủy vé | Quy trình hoàn, đổi vé |
| Chỉ có tiếng Việt | Đa ngôn ngữ |

---

## Slide 20: Phân công và đóng góp (0,5')

**Nội dung**

| Thành viên | Phụ trách |
|---|---|
| Namv | Lõi đặt vé nhiều chặng, giữ ghế, seat map phía server, điều phối và quản lý nhánh |
| Luân | Quản lý đơn đặt vé và menu admin, hành lý trong luồng đặt vé, giao diện sơ đồ ghế |
| Nguyên | Quản lý gói hành lý, hồ sơ hành khách và điền nhanh, nối các liên kết giao diện |
| Tuyết | Thiết kế giao diện Blade + Bootstrap + Soạn PowerPoint |

---

## Slide 21: Kết luận và hỏi đáp (1')

**Nội dung**
Hệ thống đã đạt mục tiêu: luồng đặt vé nhiều chặng hoàn chỉnh, giữ ghế an toàn trước tranh chấp, phân hệ quản trị đầy đủ và được kiểm thử tự động. Bài học chính của nhóm là thiết kế dữ liệu và giao dịch quan trọng hơn giao diện, và kiểm thử sớm giúp phát hiện lỗi tranh chấp dữ liệu mà bằng mắt khó thấy.

---