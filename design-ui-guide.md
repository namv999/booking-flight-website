# Design UI Guide — Website Đặt Vé Máy Bay

## 1. Mục đích của file

File này là tài liệu định hướng design UI cho **luồng đặt vé phía User**, đặc biệt là màn hình xuất hiện sau khi người dùng bấm **“Tìm kiếm chuyến bay”**.

Mục tiêu là khi thiết kế UI, cần hiểu được:

- Màn hình này nhận dữ liệu từ đâu.
- Controller nào xử lý.
- Request nào validate.
- Model/Service nào tham gia.
- Blade View nào hiển thị.
- JS/CSS nào liên quan.
- Màn hình hiện tại đã có backend hỗ trợ đến đâu.
- Những màn hình nào mới là định hướng cho phase tiếp theo.

> **Lưu ý:** Tài liệu này tập trung vào cấu trúc và kế hoạch UI. Không coi các phần UI định hướng tương lai là chức năng backend đã hoàn thiện.

---

# 2. Tổng quan UI booking flow

Luồng User dự kiến:

```text
HOME
  │
  │ Search
  ▼
FLIGHT RESULTS
  │
  │ Chọn chuyến bay
  ▼
SEAT SELECTION
  │
  │ Continue
  ▼
PASSENGER INFORMATION
  │
  │ Continue
  ▼
PAYMENT
  │
  │ Payment success
  ▼
BOOKING SUCCESS / TICKET
```

Có thể xem dưới dạng:

```text
┌──────────────┐
│    HOME      │
│  Search Box  │
└──────┬───────┘
       │ Search
       ▼
┌──────────────────────┐
│   FLIGHT RESULTS     │
│                      │
│ Search Summary       │
│                      │
│ Filter │ Flight Card │
│        │ Flight Card │
│        │ Flight Card │
└─────────┬────────────┘
          │ Select
          ▼
┌──────────────────────┐
│   SEAT SELECTION     │
│                      │
│ Flight Summary       │
│ Seat Map             │
│ Countdown            │
│                      │
│ [Continue]           │
└─────────┬────────────┘
          ▼
┌───────────────────────┐
│ PASSENGER INFORMATION │
│                       │
│ Adult / Child / Infant│
│                       │
│ [Continue Payment]    │
└─────────┬─────────────┘
          ▼
┌──────────────────────┐
│       PAYMENT        │
│                      │
│ Booking summary      │
│ Price                │
│ Payment method       │
│                      │
│ [Pay]                │
└─────────┬────────────┘
          ▼
┌────────────────────────────┐
│   BOOKING SUCCESS/FALSE    │
│                            │
│ Booking code               │
│ Ticket information         │
│ PDF ticket (if success)    │
└────────────────────────────┘
```

---

# 3. Màn hình cần design trước: Flight Results

Đây là màn hình chuyển tiếp trực tiếp từ Home sau khi search.

Route:

```text
GET /flights/results
```

Named route:

```php
flights.search.results
```

View dự kiến:

```text
resources/views/flights/results.blade.php
```

Controller:

```text
app/Http/Controllers/FlightSearchController.php
```

Request:

```text
app/Http/Requests/FlightSearchRequest.php
```

---

# 4. Flow kỹ thuật của Flight Results

Luồng dữ liệu:

```text
Home Search Form
      │
      │ GET /flights/results
      ▼
FlightSearchRequest
      │
      │ validation
      ▼
FlightSearchController@results()
      │
      ├── BookingExpiryService
      │
      ├── FlightSeat query
      │
      ├── Flight
      │
      ├── Airport
      │
      └── FareClass
      │
      ▼
flights.results Blade
      │
      ├── CSS
      └── JavaScript
      ▼
User nhìn thấy danh sách chuyến bay
```

## 4.1 Route

File:

```text
routes/web.php
```

Hiện tại có:

```php
Route::get('/flights/results', [FlightSearchController::class, 'results'])
    ->name('flights.search.results');
```

Home search form sử dụng named route này làm `action`.

---

# 5. FlightSearchController

File:

```text
app/Http/Controllers/FlightSearchController.php
```

Có hai nhiệm vụ chính:

```text
form()
results()
```

## `form()`

Dùng để hiển thị form search riêng.

Nó load:

- airports
- fare classes

và trả về:

```text
flights.search
```

### Trạng thái cần lưu ý

Trong UI flow hiện tại, Home đã có Search Box và submit trực tiếp đến:

```text
flights.search.results
```

Vì vậy `flights.search.form` không phải là màn hình UI chính cần design cho flow mới.

Nếu route này bị loại bỏ trong tương lai, mọi Controller đang:

```php
redirect()->route('flights.search.form')
```

phải được cập nhật.

---

## `results(FlightSearchRequest $request)`

Đây là Controller quan trọng nhất đối với Flight Results.

Nó:

1. Gọi `BookingExpiryService->cancelAllExpired()`.
2. Nhận dữ liệu đã được validate từ `FlightSearchRequest`.
3. Tạo query trên `FlightSeat`.
4. Xác định giá thấp nhất.
5. Đếm ghế khả dụng.
6. Lọc theo fare class.
7. Xử lý ghế đang `held` nhưng đã hết hạn.
8. Group theo flight.
9. Chỉ lấy chuyến có ghế.
10. Join với `flights`.
11. Lọc sân bay đi.
12. Lọc sân bay đến.
13. Lọc ngày khởi hành.
14. Chỉ lấy flight `scheduled` hoặc `delayed`.
15. Eager-load aircraft/airline và airports.
16. Sort theo giá thấp nhất.
17. Pagination 10 chuyến/trang.
18. Trả dữ liệu cho Blade.

View nhận:

```text
flights
departureAirport
arrivalAirport
fareClass
departureDate
adults
children
infants
```

---

# 6. FlightSearchRequest

File:

```text
app/Http/Requests/FlightSearchRequest.php
```

Vai trò:

```text
Request
  ↓
Validation
  ↓
Controller nhận dữ liệu hợp lệ
```

Không nên để UI tự quyết định validation.

UI chỉ cần hiển thị:

- input
- select
- date
- passenger count
- error message

Còn luật dữ liệu nằm ở Request.

---

# 7. Model/domain liên quan đến Flight Results

## Flight

```text
app/Models/Flight.php
```

Flight là entity trung tâm của danh sách chuyến bay.

Thông tin UI cần quan tâm:

- flight
- departure time
- arrival time
- departure airport
- arrival airport
- aircraft
- status

---

## FlightSeat

```text
app/Models/FlightSeat.php
```

Đây là model rất quan trọng đối với Results.

FlightSeat cung cấp cơ sở để xác định:

- fare class
- price
- available seats
- held seats
- expired held seats

Vì vậy UI card có thể hiển thị:

```text
Giá từ ...
Còn ... ghế
Economy / fare class
```

---

## Aircraft

```text
app/Models/Aircraft.php
```

Có quan hệ với:

```text
Airline
Seat
Flight
```

Flight Results hiện eager-load aircraft và airline để lấy thông tin hãng/máy bay.

---

## Airline

```text
app/Models/Airline.php
```

Có thể dùng cho Flight Card:

```text
Vietnam Airlines
VietJet Air
...
```

---

## Airport

```text
app/Models/Airport.php
```

UI nên hiển thị:

```text
SGN
Hồ Chí Minh

HAN
Hà Nội
```

---

## FareClass

```text
app/Models/FareClass.php
```

UI có thể hiển thị:

```text
Economy
Business
...
```

và giá.

---

# 8. Flight Results UI structure

Nên chia màn hình thành các vùng:

```text
┌────────────────────────────────────────────────────┐
│ HEADER / NAVIGATION                                │
├────────────────────────────────────────────────────┤
│ SEARCH SUMMARY                                     │
│ SGN → HAN | 27/08/2026 | 1 Adult | Economy         │
├────────────────────────────────────────────────────┤
│                                                    │
│ FILTER                 FLIGHT LIST                 │
│                                                    │
│ ┌─────────────┐       ┌────────────────────────┐   │
│ │ Hãng bay    │       │ Flight Card            │   │
│ │             │       │                        │   │
│ │ Giá         │       │ 06:30 → 08:40          │   │
│ │             │       │ SGN → HAN              │   │
│ │ Giờ bay     │       │ 2h 10m                 │   │
│ │             │       │ Economy                │   │
│ └─────────────┘       │ 2.500.000đ             │   │
│                       │ [Chọn chuyến bay]      │   │
│                       └────────────────────────┘   │
│                                                    │
│                       ┌────────────────────────┐   │
│                       │ Flight Card            │   │
│                       └────────────────────────┘   │
│                                                    │
├────────────────────────────────────────────────────┤
│ PAGINATION                                         │
└────────────────────────────────────────────────────┘
```

---

# 9. Search Summary

Nên có một khu vực tóm tắt điều kiện search ở đầu trang.

Ví dụ:

```text
Chuyến bay

SGN ───────────────→ HAN
Hồ Chí Minh          Hà Nội

Thứ Năm, 27/08/2026
1 Người lớn · Economy

[Thay đổi tìm kiếm]
```

Mục tiêu:

> Người dùng phải biết mình đang xem chuyến bay cho điều kiện nào.

Thông tin nguồn từ Controller:

```text
departureAirport
arrivalAirport
departureDate
adults
children
infants
fareClass
```

---

# 10. Flight Card

Đây là component UI quan trọng nhất của Results.

Nên ưu tiên hierarchy:

```text
1. Airline
2. Departure / Arrival time
3. Airport
4. Duration / route
5. Fare class
6. Price
7. Available seats
8. Action
```

Ví dụ:

```text
┌──────────────────────────────────────────────┐
│ Vietnam Airlines              Economy        │
│                                              │
│ 06:30                         08:40          │
│ SGN ────────────────────────→ HAN            │
│ Hồ Chí Minh                   Hà Nội         │
│                                              │
│ 2h 10m · Bay thẳng                           │
│                                              │
│                               Từ             │
│                           2.500.000đ         │
│                                              │
│ Còn 12 ghế        [ Chọn chuyến bay ]        │
└──────────────────────────────────────────────┘
```

Không nên nhồi tất cả dữ liệu database vào card.

UI chỉ lấy những dữ liệu phục vụ quyết định chọn chuyến.

---

# 11. Filter

Có thể design khu vực Filter:

```text
Bộ lọc

Hãng hàng không
☐ Vietnam Airlines
☐ VietJet Air

Khoảng giá
○ ...

Giờ khởi hành
○ 00:00 – 06:00
○ 06:00 – 12:00
○ 12:00 – 18:00
○ 18:00 – 24:00
```

### Nhưng cần phân biệt

Backend hiện tại đã hỗ trợ search theo:

- departure airport
- arrival airport
- departure date
- fare class
- availability
- flight status

Các filter UI như:

- airline
- price range
- departure time range

chưa nên coi là backend functionality đã hoàn chỉnh nếu Controller/Request chưa có logic tương ứng.

Vì vậy khi design có thể chuẩn bị UI, nhưng không nên tự gắn behavior cho chúng nếu backend chưa hỗ trợ.

---

# 12. Pagination

Controller hiện đang:

```php
->paginate(10)
->withQueryString()
```

Do đó UI Results nên chừa khu vực:

```text
                    1  2  3  4  >
```

và giữ được query search khi chuyển trang.

Không cần tự viết pagination JavaScript.

---

# 13. Sau khi chọn chuyến bay

Action:

```text
[Chọn chuyến bay]
```

sẽ dẫn đến logic hold ghế.

Controller:

```text
app/Http/Controllers/SeatSelectionController.php
```

Method:

```text
hold()
```

Luồng:

```text
Flight Results
      │
      │ POST /booking/hold
      ▼
SeatSelectionController
      │
      ├── validate
      ├── tính seatsNeeded
      ├── transaction
      ├── lockForUpdate()
      ├── tìm ghế
      ├── giữ ghế
      └── tạo pending_hold
```

---

# 14. SeatSelectionController

File:

```text
app/Http/Controllers/SeatSelectionController.php
```

Quy tắc hiện tại:

```text
Adults + Children = số ghế cần giữ
Infants = không có ghế riêng
```

Khi hold thành công:

```text
flight_seats.status = held
flight_seats.held_by = user_id
flight_seats.held_until = now + 10 phút
```

Session lưu:

```text
pending_hold
├── flight_id
├── fare_class_id
├── flight_seat_ids
├── adults
├── children
├── infants
└── expires_at
```

---

# 15. BookingExpiryService

File:

```text
app/Services/BookingExpiryService.php
```

Vai trò:

```text
Lazy expiry
```

Nó xử lý booking/payment hết hạn mà không cần Queue/Scheduler.

Có hai chức năng đáng chú ý:

```text
cancelIfExpired()
cancelAllExpired()
```

Khi booking hết hạn:

```text
Ticket.flight_seat_id → null
FlightSeat → available
held_by → null
held_until → null
Booking → cancelled
```

---

# 16. Passenger Information

Controller:

```text
app/Http/Controllers/BookingController.php
```

Request:

```text
app/Http/Requests/PassengerBookingRequest.php
```

View:

```text
resources/views/booking/passengers.blade.php
```

Luồng:

```text
pending_hold
      ↓
BookingController@create()
      ↓
Passenger Information UI
      ↓
PassengerBookingRequest
      ↓
BookingController@store()
```

Business rules đáng chú ý:

### Adult

Có ticket và ghế.

### Child

Có ticket và ghế.

### Infant

Không có ghế riêng:

```text
flight_seat_id = null
companion_adult_passenger_id = adult passenger
```

Giá infant:

```text
10% giá ticket người lớn đi cùng
```

---

# 17. Payment

Controller:

```text
app/Http/Controllers/PaymentController.php
```

View:

```text
resources/views/payment/show.blade.php
```

Luồng:

```text
Passenger Information
        ↓
Booking
        ↓
Payment
        ↓
PaymentController
        ↓
success / failed
```

Payment method hiện tại:

```text
the_tin_dung
vi_dien_tu
```

Payment expiry sử dụng:

```text
config('booking.payment_expire_minutes')
```

---

# 18. Booking Success / Ticket

Đây là phần UI định hướng phase sau.

Nên có:

```text
Booking code
Passenger information
Flight information
Seat
Fare
Total amount
Payment status
```

Và sau này:

```text
[Download PDF Ticket]
```

PDF dự kiến sử dụng:

```text
barryvdh/laravel-dompdf
```

---

# 19. Các file/folder cần biết khi design UI

## Routes

```text
routes/
└── web.php
```

Dùng để hiểu:

```text
URL
→ Controller
→ named route
```

---

## Controllers

```text
app/Http/Controllers/
├── HomeController.php
├── FlightSearchController.php
├── SeatSelectionController.php
├── BookingController.php
├── PaymentController.php
│
└── Admin/
    ├── DashboardController.php
    ├── AirlineController.php
    ├── AircraftController.php
    ├── AirportController.php
    ├── FareClassController.php
    └── FlightController.php
```

Đối với User booking flow, ưu tiên:

```text
FlightSearchController
SeatSelectionController
BookingController
PaymentController
```

---

## Form Requests

```text
app/Http/Requests/
├── FlightSearchRequest.php
└── PassengerBookingRequest.php
```

Dùng để hiểu:

```text
Input UI
→ validation
```

---

## Models

```text
app/Models/
├── User.php
├── Airline.php
├── Aircraft.php
├── Airport.php
├── Flight.php
├── Seat.php
├── FlightSeat.php
├── FareClass.php
├── Booking.php
├── BookingFlight.php
├── Passenger.php
├── Ticket.php
├── Payment.php
└── ...
```

Đối với Results, ưu tiên:

```text
Flight
FlightSeat
Aircraft
Airline
Airport
FareClass
```

Đối với Booking:

```text
Booking
BookingFlight
Passenger
Ticket
FlightSeat
```

---

## Services

```text
app/Services/
└── BookingExpiryService.php
```

Đây là service quan trọng khi thiết kế các trạng thái:

```text
available
held
booked
expired
cancelled
```

---

# 20. Blade Views

Khu vực:

```text
resources/views/
```

Các view liên quan:

```text
resources/views/
├── home.blade.php
│
├── flights/
│   ├── search.blade.php
│   └── results.blade.php
│
├── booking/
│   └── passengers.blade.php
│
├── payment/
│   └── show.blade.php
│
└── layouts/
    └── app.blade.php
```

### Khi design Flight Results

Các file cần đọc cùng nhau:

```text
routes/web.php
FlightSearchController.php
FlightSearchRequest.php
home.blade.php
flights/results.blade.php
layouts/app.blade.php
```

Đây là nhóm file quan trọng nhất.

---

# 21. CSS

Current resource structure:

```text
resources/css/
├── app.css
├── base.css
├── home.css
├── account.css
└── admin.css
```

Khi thiết kế Results nên xác định riêng stylesheet cho page nếu project tiếp tục tách page CSS:

```text
resources/css/
└── pages/
    └── flights/
        └── results.css
```

Không bắt buộc phải tạo ngay nếu kiến trúc CSS hiện tại chưa thống nhất.

Điểm cần giữ:

```text
Global CSS
      ↓
Page-specific CSS
```

tránh đưa toàn bộ style Results vào global stylesheet.

---

# 22. JavaScript

Current:

```text
resources/js/
├── app.js
├── auth.js
├── bootstrap.js
├── dashboard.js
├── home.js
└── layout.js
```

Home:

```text
home.js
```

đang liên quan đến Search Box/UI Home.

Nếu Results cần JavaScript riêng, có thể tổ chức:

```text
resources/js/
└── flights/
    └── results.js
```

Nhưng chỉ nên thêm khi Results thực sự cần behavior.

Không dùng JavaScript để thay thế backend validation hoặc backend business logic.

---

# 23. Layout

File:

```text
resources/views/layouts/app.blade.php
```

Cần kiểm tra trước khi design page để biết:

```text
header
footer
main container
@yield('content')
styles section
scripts section
```

Đây đặc biệt quan trọng vì project hiện đang có lịch sử sử dụng cả:

```text
@extends(...)
@section(...)
```

và:

```text
<x-app-layout>
```

Không nên trộn hai cơ chế layout tùy tiện trong cùng một page.

---

# 24. Assets

Ảnh public hiện dùng:

```text
public/images/
```

Ví dụ:

```text
public/images/hero-banner.jpg
```

Với Vite/resource CSS, cần phân biệt:

```text
public/
```

với:

```text
resources/
```

Asset từ public có thể được tham chiếu bằng Laravel:

```php
asset('images/hero-banner.jpg')
```

đặc biệt hữu ích khi CSS được serve qua Vite.

---

# 25. UI Design principle cho Results

Khi design Flight Results, thứ tự ưu tiên thông tin nên là:

```text
Search context
      ↓
Flight comparison
      ↓
Price
      ↓
Availability
      ↓
Action
```

Người dùng phải có khả năng trả lời nhanh:

```text
Tôi đang tìm chuyến nào?
        ↓
Chuyến nào phù hợp?
        ↓
Giá bao nhiêu?
        ↓
Còn ghế không?
        ↓
Tôi có muốn chọn không?
```

---

# 26. Booking context nên được giữ xuyên suốt

Từ Results trở đi nên giữ một context thống nhất:

```text
SGN → HAN
27/08/2026
1 Adult · 1 Child
Economy
```

Có thể thể hiện bằng:

- Search Summary
- sticky summary
- booking progress
- sidebar summary

Ví dụ progress:

```text
SEARCH
  ●
RESULTS
  ●
PASSENGERS
  ○
PAYMENT
  ○
```

Nếu có Seat Selection riêng:

```text
SEARCH
  ●
RESULTS
  ●
SEATS
  ○
PASSENGERS
  ○
PAYMENT
  ○
```

---

# 27. Những phần nên design theo phase

## Phase 1 — ưu tiên hiện tại

```text
Home
 ↓
Flight Results
```

Tập trung:

- Header
- Search Summary
- Filter area
- Flight Card
- Pagination
- Empty state
- Error state

---

## Phase 2

```text
Flight Results
 ↓
Seat Selection
 ↓
Passenger Information
```

Tập trung:

- Seat map
- Seat status
- Countdown
- Selected seat summary
- Passenger forms
- Booking summary

---

## Phase 3

```text
Passenger Information
 ↓
Payment
 ↓
Booking Success
```

Tập trung:

- Payment summary
- Payment method
- Payment status
- Booking code
- Ticket
- PDF download

---

# 28. State UI cần tính đến

Không chỉ design trạng thái "có kết quả".

Flight Results nên có ít nhất:

```text
1. Loading
2. Has results
3. No results
4. Validation error
5. Search expired/invalid
6. Pagination
```

Sau khi chọn flight:

```text
7. Hold success
8. Not enough seats
9. Hold expired
10. User already has an active hold
```

Payment:

```text
11. Payment pending
12. Payment success
13. Payment failed
14. Booking expired
```

---

# 29. Checklist trước khi design Flight Results

Đọc theo thứ tự:

```text
[1] routes/web.php

[2] FlightSearchController.php

[3] FlightSearchRequest.php

[4] Flight.php
[5] FlightSeat.php
[6] Airport.php
[7] Aircraft.php
[8] Airline.php
[9] FareClass.php

[10] home.blade.php

[11] flights/results.blade.php

[12] layouts/app.blade.php

[13] resources/css/app.css
[14] resources/css/base.css
[15] resources/css/home.css

[16] resources/js/home.js
[17] resources/js/layout.js
```

Sau đó mới chuyển sang:

```text
SeatSelectionController
        ↓
BookingController
        ↓
PassengerBookingRequest
        ↓
PaymentController
        ↓
BookingExpiryService
```

---

# 30. Tóm tắt kiến trúc để designer/dev cùng hiểu

```text
                    ROUTE
                      │
                      ▼
             FlightSearchController
                      │
             ┌────────┴────────┐
             ▼                 ▼
     FlightSearchRequest     Service
             │                 │
             └────────┬────────┘
                      ▼
                    MODEL
                      │
        ┌─────────────┼─────────────┐
        ▼             ▼             ▼
      Flight      FlightSeat      Airport
        │             │
        ▼             ▼
     Aircraft      FareClass
        │
        ▼
      Airline
                      │
                      ▼
                  BLADE VIEW
                      │
             ┌────────┴────────┐
             ▼                 ▼
             CSS               JS
```

Và sau khi User chọn chuyến:

```text
Flight Results
      │
      ▼
SeatSelectionController
      │
      ▼
BookingExpiryService
      │
      ▼
BookingController
      │
      ▼
PaymentController
      │
      ▼
Booking / Ticket / Payment
```

**Điểm quan trọng nhất:** Khi design UI, không nên chỉ nhìn `results.blade.php`. Cần nhìn ngược lên **Route → Controller → Request → Model/Service** để biết chính xác dữ liệu mà UI thực sự có thể hiển thị và behavior nào backend đã hỗ trợ.