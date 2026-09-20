# Binding Data Notes — Flight Results / Passenger Information / Payment

## 0. File này dùng để làm gì

File này **bổ sung cho `design-ui-guide.md`** (không thay thế). `design-ui-guide.md` mô tả kiến trúc tổng thể + UI structure định hướng; file này liệt kê **chính xác biến/field/route** mà 3 màn hình đang có backend thật hỗ trợ, để Agent design không tự bịa field không tồn tại.

Nguyên tắc giữ xuyên suốt (theo đúng mục 25/26 của `design-ui-guide.md`):

```text
Search context (giữ từ Results → Passengers → Payment)
      ↓
Flight comparison / Price / Availability
      ↓
Action
```

Quy ước layout/session chung cho cả 3 màn (không đổi):

```text
@extends('layouts.app')
@section('title') / @section('content') / @section('scripts')
session('error') / session('status')  → khu vực hiển thị flash message
@csrf bắt buộc trong mọi <form> POST
```

Comment `{{-- TEMP LAYOUT --}}` ở đầu 3 file hiện tại sẽ được xóa khi design mới thay thế.

---

## 1. Flight Results — `resources/views/flights/results.blade.php`

Route: `GET /flights/results` → named route `flights.search.results`
Controller: `FlightSearchController@results()`
Request: `FlightSearchRequest`

### Biến Controller truyền vào Blade

| Biến | Nguồn | Ghi chú |
|---|---|---|
| `$flights` | `Flight` + join `FlightSeat` | Paginator, `paginate(10)->withQueryString()` |
| `$departureAirport` | `Airport::findOrFail()` | dùng cho Search Summary |
| `$arrivalAirport` | `Airport::findOrFail()` | dùng cho Search Summary |
| `$fareClass` | `FareClass::findOrFail()` | dùng cho Search Summary |
| `$departureDate` | string (raw input đã validate) | cần `Carbon::parse()` khi format |
| `$adults`, `$children`, `$infants` | int | dùng cho Search Summary |

### Mỗi `$flight` trong vòng lặp (Flight Card)

```text
$flight->id
$flight->status                → scheduled | delayed | cancelled
$flight->departure_time        → Carbon (cast sẵn)
$flight->arrival_time          → Carbon (cast sẵn)
$flight->min_price             → từ join FlightSeat, KHÔNG phải cột thật của Flight
$flight->available_seats       → từ join FlightSeat, KHÔNG phải cột thật của Flight
$flight->aircraft->model
$flight->aircraft->airline->name
$flight->departureAirport->iata_code / ->city
$flight->arrivalAirport->iata_code / ->city
```

> `min_price` và `available_seats` là alias từ subquery join, không gọi được qua relation khác ngoài object `$flight` đang lặp.

### Filter (Hãng bay / Khoảng giá / Giờ bay) — CHƯA có backend

`FlightSearchRequest` hiện chỉ validate: `departure_airport_id`, `arrival_airport_id`, `departure_date`, `fare_class_id`, `adults`, `children`, `infants`. Agent có thể **vẽ UI filter** (đúng như mục 11 của `design-ui-guide.md`) nhưng **không gắn behavior thật** — nếu submit sẽ không có tham số nào được Controller đọc.

### Action "Chọn chuyến bay" (giữ nguyên field name)

```
POST route('booking.hold')
- flight_id       (hidden)
- fare_class_id   (hidden)
- adults          (hidden)
- children        (hidden)
- infants         (hidden)
```
Bắt buộc là nút submit form, không phải `<a href>`.

### Pagination

`{{ $flights->links() }}` — mặc định Laravel, giữ `withQueryString()` nên không cần JS tự viết lại (đúng mục 12 guide).

### State cần thiết kế

```text
1. Has results        → Flight Card list
2. No results          → @empty block, message "Không tìm thấy chuyến bay phù hợp"
3. Validation error     → $errors->all() (khi GET thiếu param hợp lệ, redirect back() kèm errors)
4. Flash error/status   → session('error') / session('status') (ví dụ: "Không đủ ghế trống" khi quay lại từ booking.hold)
```

---

## 2. Passenger Information — `resources/views/booking/passengers.blade.php`

Route: `GET/POST /booking/passengers` → `booking.passengers.form` / `booking.passengers.store`
Controller: `BookingController@create()` / `@store()`
Request: `PassengerBookingRequest`

### Biến Controller truyền vào Blade

| Biến | Nguồn | Ghi chú |
|---|---|---|
| `$flight` | `Flight` (eager load `departureAirport`, `arrivalAirport`, `aircraft.airline`) | dùng cho Flight/Booking Summary giữ context |
| `$pending` | session `pending_hold` | array: `flight_id`, `fare_class_id`, `flight_seat_ids`, `adults`, `children`, `infants`, `expires_at` |

`$pending['adults']`/`children`/`infants` chỉ là **số lượng (int)**, chưa có data hành khách thật — form phải render đúng số lượng field bằng `@for`, không phải `@foreach` trên data có sẵn.

### Field name form (PHẢI giữ nguyên chính xác — `PassengerBookingRequest` đọc theo tên này)

```
POST route('booking.passengers.store')

adults[i][full_name]                   required
adults[i][document_number]             optional
adults[i][date_of_birth]               optional

children[i][full_name]                 required
children[i][document_number]           optional
children[i][date_of_birth]             optional

infants[i][full_name]                  required
infants[i][document_number]            optional
infants[i][date_of_birth]              optional
infants[i][companion_adult_index]      required — index 0-based trong mảng adults[],
                                        KHÔNG PHẢI passenger_id thật (passenger chưa
                                        tồn tại trong DB lúc submit)
```

`i` chạy từ `0` đến `$pending['adults']-1` (tương tự children/infants).

### Business rule cần thể hiện đúng trên UI (theo mục 16 guide)

```text
Adult  → có ghế + ticket riêng
Child  → có ghế + ticket riêng
Infant → KHÔNG có ghế riêng, bắt buộc chọn "đi kèm người lớn nào",
         giá vé = 10% giá vé người lớn đi cùng (tính ở Controller, không hiển thị % này ở form nhập)
```

### KHÔNG được tự thêm field (chưa có backend xử lý)

`passengers` / `saved_passengers` trong DB đã có cột `nationality`, `document_issued_country`, `document_expiry_date`, `document_type` — nhưng `PassengerBookingRequest` **chưa validate** và `BookingController@store()` **chưa lưu** các field này. Nếu agent tự vẽ thêm input passport, dữ liệu nhập vào sẽ **bị lờ đi hoàn toàn**, không báo lỗi, không lưu. Cần báo lại nếu muốn dùng ngay, để mở rộng Request + Controller trước khi merge UI.

Flow "Risk Warning → Checkbox Confirmation → Payment" cho passport dưới 6 tháng (mô tả trong schema doc) **chỉ là thiết kế dự kiến, chưa có code** — không vẽ như tính năng đã chạy.

### State cần thiết kế

```text
1. Form nhập hợp lệ
2. Validation error         → $errors->all()
3. Hold hết hạn khi vào trang  → redirect flights.search.form + session('error')
   (Controller check ngay ở create(), không render form nếu hết hạn)
```

---

## 3. Payment — `resources/views/payment/show.blade.php`

Route: `GET/POST /payment/{booking}` → `payment.show` / `payment.store`
Controller: `PaymentController@show()` / `@store()`

### Biến Controller truyền vào Blade

| Biến | Nguồn | Ghi chú |
|---|---|---|
| `$booking` | `Booking` (eager load `bookingFlights.flight`, `bookingFlights.tickets`) | |
| `$expiresAt` | Carbon = `$booking->created_at + config('booking.payment_expire_minutes')` (15p) | tính 1 lần lúc render — **cần cho countdown JS ở mục 3.3** |

### Cấu trúc lặp hiển thị chi tiết vé

```text
$booking->id
$booking->total_amount
$booking->bookingFlights (collection)
  → ->flight->departure_time
  → ->tickets (collection)
      → ->passenger->full_name
      → ->passenger->passenger_type   (adult | child | infant)
      → ->price
```

> ⚠️ **Cần fix trước khi bind UI thật:** `PaymentController@show()` hiện **chưa** eager-load `departureAirport`/`arrivalAirport` cho `flight`, nên field đang có sẵn chỉ là `flight->departure_airport_id` / `arrival_airport_id` (số ID thô, không dùng được cho UI). Sẽ bổ sung `.with('bookingFlights.flight.departureAirport', 'bookingFlights.flight.arrivalAirport')` ở Controller. Agent thiết kế UI theo đúng field đích:
> ```text
> $bf->flight->departureAirport->iata_code / ->city
> $bf->flight->arrivalAirport->iata_code / ->city
> ```
> chứ không bind theo ID thô hiện tại — coi như field này **sẽ có**, không phải field cần agent tự suy ra cách khác.

### 3.1 Payment method (giữ đúng 2 giá trị)

```
name="method"
- value="the_tin_dung"   → "Thẻ tín dụng"
- value="vi_dien_tu"     → "Ví điện tử"
```

### 3.2 Hai nút mô phỏng thanh toán — GIỮ NGUYÊN, không gộp lại

Đây là chủ đích thiết kế (mô phỏng thanh toán cho đồ án, không có cổng thanh toán thật), **không phải placeholder cần thay bằng 1 nút thật**:

```
POST route('payment.store', $booking)

<button type="submit" name="simulate_result" value="success">Giả lập THÀNH CÔNG</button>
<button type="submit" name="simulate_result" value="failed">Giả lập THẤT BẠI</button>
```
Cả 2 dùng chung `name="simulate_result"`, khác nhau `value`. Agent có thể style lại thành 2 nút rõ ràng khác màu (ví dụ xanh "thành công" / đỏ "thất bại") nhưng **bắt buộc giữ 2 nút riêng biệt**, không được gộp thành 1 nút rồi random kết quả bằng JS — kết quả phải do người test bấm chọn tay.

### 3.3 Countdown JS cho hạn thanh toán thật (mới — cần bổ sung)

Hiện tại `payment/show.blade.php` chỉ render tĩnh 1 lần lúc load trang:
```blade
{{ now()->diffForHumans($expiresAt, true) }}
```
→ không tự chạy lùi, F5 mới thấy số mới. Cần thay bằng countdown JS thật.

**Binding cho JS (Blade side):**
```blade
<span id="payment-countdown" data-expires-at="{{ $expiresAt->toIso8601String() }}">
    còn {{ now()->diffForHumans($expiresAt, true) }}
</span>
```
`data-expires-at` là nguồn duy nhất JS cần đọc — không hardcode số phút (15) trong JS, vì `payment_expire_minutes` cấu hình ở `config/booking.php`, có thể đổi.

**Quy tắc bắt buộc cho JS (agent code phần này, hoặc mình viết sau khi agent chừa đúng chỗ):**
```text
1. Đọc data-expires-at, tính khoảng cách tới hiện tại, hiển thị dạng mm:ss, tick mỗi giây.
2. Khi đếm về 0: KHÔNG tự ý coi là đã hủy booking ở phía client.
   → JS chỉ có nhiệm vụ HIỂN THỊ, việc hủy booking thật nằm ở
     BookingExpiryService::cancelIfExpired() chạy phía server.
   → Khi countdown = 0, JS nên tự động reload lại trang (hoặc gọi lại
     route('payment.show', $booking)) để server re-check và redirect
     đúng (về trang chủ kèm session('error') "Booking đã hết hạn...").
3. Không dùng JS để thay validation/business logic (đúng nguyên tắc
   chung ở mục 22 design-ui-guide.md).
```

**Vị trí file JS đề xuất** (theo đúng convention tách JS theo trang của project):
```text
resources/js/payment.js
```
nạp qua `@section('scripts') @vite(['resources/js/payment.js']) @endsection`.

### State cần thiết kế

```text
1. Payment pending (form thanh toán bình thường)
2. Payment success       → session('status'), redirect (tạm thời về flights.search.form,
                            sẽ đổi route('booking.history') khi B làm xong — không thiết kế
                            trang riêng, chỉ cần message flash đủ rõ)
3. Payment failed         → session('error'), quay lại chính trang payment.show
4. Booking đã paid rồi    → redirect kèm session('status'), không cho vào lại form
5. Booking expired/cancelled → redirect kèm session('error')
6. Truy cập booking người khác → 403 (không cần thiết kế UI riêng, dùng error page chung)
```

---

## 4. Checklist file cần đọc cùng nhau (bổ sung mục 29 của design-ui-guide.md)

```text
[1] routes/web.php
[2] app/Http/Controllers/BookingController.php
[3] app/Http/Controllers/PaymentController.php
[4] app/Http/Requests/PassengerBookingRequest.php
[5] app/Services/BookingExpiryService.php
[6] config/booking.php
[7] resources/views/booking/passengers.blade.php
[8] resources/views/payment/show.blade.php
[9] resources/views/layouts/app.blade.php
```

## 5. Tóm tắt route liên quan (không đổi tên, không đổi field)

```text
GET  flights.search.results        → flights/results.blade.php
POST booking.hold                   (không có view, redirect)
GET  booking.passengers.form       → booking/passengers.blade.php
POST booking.passengers.store       (không có view riêng, redirect payment.show)
GET  payment.show/{booking}        → payment/show.blade.php
POST payment.store/{booking}        (không có view riêng, redirect)
```
