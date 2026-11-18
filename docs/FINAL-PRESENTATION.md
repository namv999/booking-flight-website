# Website đặt vé máy bay — sơ đồ và nội dung thuyết trình

Tài liệu này được đối chiếu với routes, migrations và nghiệp vụ trong source hiện tại. Ba sơ đồ Mermaid có thể xuất thành SVG/PNG từ Mermaid Live Editor để chèn vào slide. ERD dưới đây được dựng từ 15 bảng nghiệp vụ trong migrations; repo hiện không có `DB_IDEA_4.md`.

## Sơ đồ 1 — Use case

```mermaid
flowchart LR
    customer[Khách hàng]
    admin[Quản trị viên]
    subgraph system[Website đặt vé máy bay]
        account((Quản lý tài khoản và hồ sơ))
        search((Tìm và chọn chuyến bay))
        booking((Nhập hành khách và đặt vé))
        extras((Chọn ghế và hành lý))
        payment((Thanh toán mô phỏng))
        ticket((Xem lịch sử và tải vé PDF))
        saved((Quản lý hành khách đã lưu))
        catalog((Quản lý danh mục))
        flights((Quản lý chuyến bay))
        adminBooking((Tra cứu đơn đặt vé))
        dashboard((Xem thống kê))
        passport((Duyệt yêu cầu cập nhật hộ chiếu))
    end
    customer --- account
    customer --- search
    customer --- booking
    customer --- extras
    customer --- payment
    customer --- ticket
    customer --- saved
    admin --- catalog
    admin --- flights
    admin --- adminBooking
    admin --- dashboard
    admin --- passport
```

## Sơ đồ 2 — Kiến trúc tổng quan

```mermaid
flowchart LR
    browser[Trình duyệt<br/>Blade + Bootstrap + JavaScript + Chart.js]
    routes[Route + Middleware<br/>web.php, auth, admin]
    request[Form Request<br/>validate input]
    controller[Controller<br/>HTTP và phản hồi]
    service[Service<br/>đặt vé, giữ ghế, hết hạn,<br/>kiểm tra hành trình và hộ chiếu]
    models[Eloquent Models]
    db[(MariaDB / MySQL)]
    mail[Laravel Mail + Dompdf]
    browser --> routes --> request --> controller
    controller --> service --> models --> db
    controller --> browser
    controller --> mail --> inbox[Hộp thư khách hàng]
```

## Sơ đồ 3 — Đặt vé và thanh toán

```mermaid
sequenceDiagram
    actor C as Khách hàng
    participant B as Trình duyệt
    participant R as Route / Middleware
    participant S as BookingCreationService
    participant DB as MariaDB
    participant P as PaymentController
    participant M as Mail + Dompdf

    C->>B: Tìm chuyến, nhập hành khách, chọn ghế
    B->>R: POST /booking/seats
    R->>S: Dữ liệu đã validate + lựa chọn
    S->>DB: BEGIN TRANSACTION
    S->>DB: Xét chặng theo giờ bay, khóa ghế theo thứ tự chặng rồi ID
    S->>DB: Kiểm tra ghế, tạo booking pending, vé và giá snapshot
    S->>DB: Đổi ghế available → held, gắn held_by và held_until
    S->>DB: COMMIT
    S-->>B: Chuyển tới trang thanh toán

    C->>B: Xác nhận thanh toán mô phỏng
    B->>R: POST /payment/{booking}
    R->>P: Validate phương thức và xác nhận hộ chiếu nếu cần
    P->>DB: BEGIN TRANSACTION, khóa booking trước
    P->>DB: Kiểm tra pending, hạn giữ, rồi khóa ghế theo ID
    alt Booking còn hạn và ghế vẫn held bởi chủ booking
        P->>DB: Tạo payment success, booking → paid, ghế → booked
        P->>DB: COMMIT
        P->>M: Gửi email đồng bộ kèm vé PDF
        M-->>C: Email xác nhận (lỗi SMTP được ghi log)
    else Booking hết hạn hoặc ghế không còn được giữ
        P->>DB: Không ghi payment success
        P-->>B: Báo hết hạn hoặc yêu cầu đặt lại
    end
```

## ERD — 15 bảng nghiệp vụ

```mermaid
erDiagram
    USERS ||--o{ BOOKINGS : owns
    USERS ||--o{ SAVED_PASSENGERS : saves
    USERS ||--o{ FLIGHT_SEATS : holds
    USERS ||--o{ PASSPORT_UPDATE_REQUESTS : submits
    USERS o|--o{ PASSPORT_UPDATE_REQUESTS : reviews
    AIRLINES ||--o{ AIRCRAFTS : operates
    AIRCRAFTS ||--o{ SEATS : contains
    AIRCRAFTS ||--o{ FLIGHTS : assigned
    AIRPORTS ||--o{ FLIGHTS : departure
    AIRPORTS ||--o{ FLIGHTS : arrival
    FLIGHTS ||--o{ FLIGHT_SEATS : offers
    FARE_CLASSES ||--o{ FLIGHT_SEATS : prices
    BOOKINGS ||--o{ BOOKING_FLIGHTS : includes
    FLIGHTS ||--o{ BOOKING_FLIGHTS : selected
    BOOKINGS ||--o{ PASSENGERS : names
    BOOKING_FLIGHTS ||--o{ TICKETS : issues
    PASSENGERS ||--o{ TICKETS : travels
    FLIGHT_SEATS o|--o| TICKETS : assigned
    BAGGAGE_ADDONS o|--o{ TICKETS : optional_addon
    BOOKINGS ||--o| PAYMENTS : paid_by
    BOOKINGS ||--o{ PASSPORT_UPDATE_REQUESTS : has
    PASSENGERS ||--o{ PASSPORT_UPDATE_REQUESTS : updates
    PASSENGERS o|--o{ TICKETS : infant_companion

    USERS {
        bigint id PK
    }
    AIRLINES {
        bigint id PK
    }
    AIRPORTS {
        bigint id PK
    }
    AIRCRAFTS {
        bigint id PK
        bigint airline_id FK
    }
    SEATS {
        bigint id PK
        bigint aircraft_id FK
        string seat_number
    }
    FARE_CLASSES {
        bigint id PK
        decimal seat_selection_fee
    }
    BAGGAGE_ADDONS {
        bigint id PK
    }
    FLIGHTS {
        bigint id PK
        bigint aircraft_id FK
        bigint departure_airport_id FK
        bigint arrival_airport_id FK
    }
    FLIGHT_SEATS {
        bigint id PK
        bigint flight_id FK
        bigint seat_id FK
        bigint fare_class_id FK
        string status
        datetime held_until
    }
    BOOKINGS {
        bigint id PK
        bigint user_id FK
        string status
        decimal total_amount
    }
    BOOKING_FLIGHTS {
        bigint id PK
        bigint booking_id FK
        bigint flight_id FK
    }
    PASSENGERS {
        bigint id PK
        bigint booking_id FK
        string passenger_type
    }
    SAVED_PASSENGERS {
        bigint id PK
        bigint user_id FK
    }
    TICKETS {
        bigint id PK
        bigint booking_flight_id FK
        bigint passenger_id FK
        bigint flight_seat_id FK
        bigint baggage_addon_id FK
        decimal price
    }
    PAYMENTS {
        bigint id PK
        bigint booking_id FK
        string status
        decimal amount
    }
    PASSPORT_UPDATE_REQUESTS {
        bigint id PK
        bigint booking_id FK
        bigint passenger_id FK
        bigint user_id FK
        bigint admin_id FK
    }
```

## Nội dung PowerPoint — 18 slide

Mỗi slide giữ tối đa bốn ý chính. Slide 4, 5, 6 và 8 ưu tiên sơ đồ tương ứng ở trên.

1. **Tiêu đề** — Website đặt vé máy bay; tên môn; nhóm 4 thành viên; giảng viên.
2. **Vấn đề và mục tiêu** — Đặt vé trực tuyến cần tránh giữ trùng ghế; hoàn tất hành trình từ tìm chuyến đến vé điện tử; vai trò khách hàng và quản trị viên.
3. **Công nghệ** — Laravel 12, Blade, Bootstrap, JavaScript/fetch; MariaDB/MySQL và Eloquent; Breeze, Form Request, Dompdf, Laravel Mail, Chart.js; monolith giúp nhóm triển khai và vận hành gọn.
4. **Use case** — Chèn sơ đồ 1; nhóm chức năng khách hàng và quản trị viên.
5. **Kiến trúc** — Chèn sơ đồ 2; request qua route/middleware, controller và service đến database.
6. **Thiết kế dữ liệu** — Chèn ERD; 15 bảng nghiệp vụ; ghế vật lý `seats` tách ghế từng chuyến `flight_seats`; `booking_flights` hỗ trợ nhiều chặng; giá vé lưu snapshot ở `tickets.price`.
7. **Hành trình người dùng** — Tìm chuyến, chọn chuyến từng chặng, nhập hành khách, chọn ghế, thanh toán, xem lịch sử và tải PDF; một chiều, khứ hồi, đa chặng.
8. **Sequence đặt vé và thanh toán** — Chèn sơ đồ 3; nhấn mạnh hai lần transaction: giữ ghế khi tạo booking và xác nhận ghế khi thanh toán.
9. **Giữ ghế và chống đặt trùng** — Hold 20 phút; lazy expiry; transaction và `lockForUpdate`; thiếu ghế ở một chặng thì rollback toàn booking; trạng thái ghế `available → held → booked`.
10. **Quy tắc giá** — Giá chỗ + phí chọn ghế tự chọn + hành lý; em bé 10% giá ghế gốc của người lớn đi kèm; hành lý tính theo từng chặng; tổng booking cộng mọi vé và chặng.
11. **Ghế, hành lý, hộ chiếu** — Ghế riêng theo chặng, tự gán nếu bỏ qua; mỗi khách tối đa một gói hành lý, em bé không mua; chuyến quốc tế yêu cầu thông tin hộ chiếu và xác nhận cảnh báo còn hạn dưới sáu tháng.
12. **Quản trị** — CRUD danh mục và chuyến bay; tìm kiếm, lọc, phân trang đơn; dashboard doanh thu theo tháng, chuyến theo hãng và tỷ lệ lấp đầy; duyệt yêu cầu cập nhật hộ chiếu.
13. **Bảo mật và phân quyền** — Middleware `auth`/`admin`; booking tải theo chủ sở hữu; kiểm tra dữ liệu đầu vào; soft delete danh mục giữ liên kết lịch sử.
14. **Kiểm thử** — Cập nhật số liệu sau khi chạy `php artisan test`, `vendor/bin/pint --test` và `npm run build`; nêu các luồng đã kiểm thử thủ công theo kết quả thực tế.
15. **Demo 5 phút** — Đăng nhập; tìm khứ hồi; nhập 2 người lớn + 1 em bé; chọn ghế; thanh toán; mở lịch sử và PDF; xem đơn và dashboard admin.
16. **Hạn chế** — Thanh toán mô phỏng; email gửi đồng bộ; phí chọn ghế theo hạng vé; một số kiểm tra nghiệp vụ còn inline trong controller.
17. **Hướng phát triển** — Cổng thanh toán sandbox; queue email; phí ghế theo vị trí; đa ngôn ngữ; đổi hoặc hủy vé.
18. **Phân công và Q&A** — Namv: lõi đặt vé, điều phối; Luân: admin, hành lý, sơ đồ ghế; Nguyên: hồ sơ hành khách, tiện ích; thành viên còn lại: giao diện.

## Câu hỏi thường gặp

- **Hai người cùng đặt một ghế?** Transaction khóa ghế bằng `lockForUpdate`; request sau kiểm tra trạng thái sau khi lấy được khóa. Ràng buộc duy nhất trên `tickets.flight_seat_id` là lớp bảo vệ bổ sung.
- **Ghế giữ mãi nếu khách rời trang?** Sau 20 phút, sơ đồ ghế xem hold đã hết hạn như ghế trống; booking bị hủy khi hết hạn được xử lý qua luồng tìm chuyến hoặc trang thanh toán.
- **Vì sao chưa dùng queue/scheduler?** Quy trình chạy được trong môi trường local mà không cần tiến trình nền; email hiện gửi đồng bộ.
- **Vì sao thanh toán mô phỏng?** Phạm vi hiện tại chưa tích hợp cổng thanh toán thực; muốn nhận callback cần endpoint có thể truy cập công khai.
