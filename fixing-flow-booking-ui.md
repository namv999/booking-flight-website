# Fixing Flow Booking UI — Seat-hold Flow Redesign

Tài liệu note lại thay đổi flow đặt vé (seat-hold) vừa hoàn thành ở backend, dùng để bàn giao cho Agent AI thiết kế UI. Không phải code, chỉ là ghi chú binding data + hành vi cần thể hiện trên giao diện.

## 1. Tóm tắt thay đổi flow

**Flow cũ:**
```
Search Flight → Searching → Flight Lists → Select Flight ----(Holding Seat)----> Fill Passengers' Information → Continue to Payment → Paid Success/Fail
```

**Flow mới:**
```
Search Flight → Searching → Flight Lists → Select Flight (Availability Pre-check, không lock)
→ Fill Passengers' Information → Continue (FINAL Availability Check + ATOMIC Seat Hold, 1 transaction)
→ Countdown 20:00 (chưa code UI, để sau) → Payment → Paid Success/Fail
→ Success: Confirm Booking (booking-history) | Fail/Expire: Release Seat
```

Thay đổi cốt lõi: **thời điểm giữ ghế** dời từ lúc "Select Flight" sang lúc "Continue" (submit form hành khách). Bước "Select Flight" giờ chỉ làm pre-check mềm (đếm ghế trống, không lock, không tạo hold).

## 2. Backend đã đổi (tham khảo, không cần code lại)

- Route `booking.hold` → đổi tên thành **`booking.precheck`** (`POST /booking/precheck`)
- Session key `pending_hold` → đổi thành **`pending_selection`**, chỉ còn `flight_id, fare_class_id, adults, children, infants` — **không còn** `flight_seat_ids`, **không còn** `expires_at` ở bước này (vì chưa có ghế nào bị giữ)
- `config/booking.php`: gộp `seat_hold_minutes` + `payment_expire_minutes` (15+10 phút cũ) thành **1 mốc duy nhất `seat_hold_minutes = 20`**, tính từ lúc submit passenger form thành công (ATOMIC hold)
- Redirect đích sau khi thanh toán thành công / mở lại booking đã `paid` / booking bị `cancelled` do hết hạn: đổi từ `route('home')` sang **`route('booking-history.show', $booking)`**

### Bảng message theo từng điểm redirect (dùng để hiển thị đúng nội dung, đúng màu alert)

| Điểm trigger | Redirect đích | Session key | Nội dung |
|---|---|---|---|
| Pre-check không đủ ghế (Select Flight) | `back()` về `flights/results.blade.php` | `error` | "Không đủ ghế trống cho hạng vé này, vui lòng thử lại hoặc chọn chuyến khác." |
| Vào thẳng passenger form không qua pre-check | `home` | `error` | "Vui lòng chọn chuyến bay trước khi nhập thông tin hành khách." |
| Final check thất bại lúc Continue (ghế vừa hết) | `back()->withInput()` về `booking/passengers.blade.php` | `error` | "Rất tiếc, ghế vừa hết trong lúc bạn điền thông tin. Vui lòng thử lại." |
| Đặt vé + atomic hold thành công | `payment.show` | `status` | "Đặt vé thành công, tiến hành thanh toán." |
| Thanh toán thành công | `booking-history.show` | `status` | "Thanh toán thành công! Mã booking #..." |
| Thanh toán thất bại | `payment.show` (ở lại trang) | `error` | "Thanh toán thất bại, vui lòng thử lại." |
| Booking hết hạn 20 phút (mở lại `payment.show`, hoặc double-submit) | `booking-history.show` | `error` | "Phiên đặt vé của bạn đã hết hạn. Ghế đã được giải phóng, vui lòng đặt lại." |
| Mở lại link payment/double-submit của booking đã `paid` | `booking-history.show` | `status` | "Booking này đã thanh toán rồi." |

---

## PHẦN 1 — UI đã có sẵn (Home Search & Results, Passenger Information) — cần binding lại data

### 1.1 `home.blade.php`

- **Chưa render session flash message.** Cần thêm block hiển thị `session('error')` / `session('status')`, giống cách `flights/results.blade.php` đang làm (alert Bootstrap danger/success). Đây là trang đích duy nhất còn lại nhận redirect kèm message (case "vào thẳng passenger form không qua pre-check").

### 1.2 `flights/results.blade.php`

- Nút **"Chọn"** hiện đang POST tới route `booking.hold` (tên cũ) — phải đổi target sang route **`booking.precheck`**. Route name đổi nhưng hành vi UI (form POST, các field gửi kèm: `flight_id`, `fare_class_id`, `adults`, `children`, `infants`) giữ nguyên không đổi.
- Cơ chế hiển thị `session('error')` (khi pre-check không đủ ghế) đã có sẵn — giữ nguyên, không cần sửa.
- Không có thay đổi nào khác ở trang này.

### 1.3 `booking/passengers.blade.php`

- **Quan trọng:** trang này **không còn khái niệm "ghế đang được giữ" ở thời điểm hiển thị form.** Nếu UI hiện tại có countdown/timer hoặc dòng chữ kiểu "Ghế được giữ đến HH:mm" dựa trên `expires_at` từ session — **phải bỏ**, vì tại bước này chưa có hold nào tồn tại (session `pending_selection` không có `expires_at`). Ghế chỉ thực sự bị giữ **sau khi** bấm nút Continue và transaction xử lý xong.
- Nút submit (**Continue**) giờ mang ý nghĩa nặng hơn trước: nó không chỉ tạo booking mà còn là điểm thực hiện Final Check + Atomic Hold. Cân nhắc thêm loading state rõ ràng khi bấm (tránh double-click double-submit), vì request này chậm hơn (có `lockForUpdate()` + nhiều insert trong 1 transaction).
- Cần render `session('error')` khi quay lại chính trang này do lỗi `NOT_ENOUGH_SEATS` (redirect kiểu `back()->withInput()`), đồng thời **giữ lại dữ liệu đã nhập** — dùng `old('adults.*.full_name')` v.v. cho từng field thay vì để trắng, vì controller đã gửi kèm `withInput()`.
- Không đổi cấu trúc field `name` (vẫn đúng theo `PassengerBookingRequest`: `adults.*.full_name`, `infants.*.companion_adult_index`, v.v.) — chỉ thêm phần hiển thị lỗi + giữ input.

---

## PHẦN 2 — UI chưa làm (Payment, Booking History Detail) — note binding cho thiết kế mới

### 2.1 `payment/show.blade.php`

Data controller trả về: `$booking` (đã load `bookingFlights.flight`, `bookingFlights.tickets`), `$expiresAt` (Carbon instance = `booking->created_at + 20 phút`).

- Hiển thị đếm ngược **20 phút** tới hạn thanh toán — nhưng **chưa cần code countdown JS real-time** ở bản này, chỉ cần hiển thị **text tĩnh** thời điểm hết hạn (`{{ $expiresAt->format('H:i:s d/m/Y') }}`). Countdown động để làm sau (đã note vào backlog "sáng tạo").
- Cần render `session('error')` (case thanh toán thất bại, case fallback `default` "Booking không còn ở trạng thái chờ thanh toán") và `session('status')` nếu có.
- Form thanh toán: 2 field bắt buộc gửi lên `payment.store` — `method` (`the_tin_dung` hoặc `vi_dien_tu`), `simulate_result` (`success` hoặc `failed`) — đây là mô phỏng thanh toán, không tích hợp cổng thanh toán thật, nên UI cần có cách để user (hoặc tester) chọn rõ ràng 2 outcome giả lập này (không phải random).
- Hiển thị chi tiết booking: danh sách hành khách theo `bookingFlights.tickets` (mỗi ticket có `passenger`, `flight_seat` nullable cho infant, `price`), thông tin chuyến bay từ `bookingFlights.flight`.

### 2.2 `booking-history/show.blade.php`

Data controller trả về `$booking` đã load đầy đủ: `passengers`, `payment`, `bookingFlights.flight.aircraft/airline/departureAirport/arrivalAirport` (kèm `withTrashed()` vì airline/airport có thể đã bị soft-delete), `bookingFlights.tickets.passenger/flightSeat.seat/baggageAddon`.

- **Trang này giờ là redirect đích cho 3 trường hợp** (không chỉ đơn thuần "xem lại lịch sử"), nên **bắt buộc phải render `session('error')` / `session('status')`**:
  - Booking `cancelled` do hết hạn 20 phút → hiển thị alert `error`
  - Booking `paid` mở lại (double-submit hoặc mở lại link cũ) → hiển thị alert `status`
  - Vừa thanh toán thành công → hiển thị alert `status` (đây là màn hình xác nhận đặt vé thành công, nên alert này nên nổi bật)
- Vì có thể hiển thị cho cả 3 trạng thái `status` (`pending`, `paid`, `cancelled`), UI cần có badge/label trạng thái rõ ràng theo từng status, tối thiểu phân biệt màu sắc (vd: paid = xanh, cancelled = xám/đỏ, pending = vàng — pending hiếm khi xuất hiện ở trang này vì hoặc đã paid hoặc đã cancelled khi tới được đây).
- Với booking `cancelled`: `tickets.flight_seat_id` đã bị nullify (ghế đã trả về hệ thống) — nếu UI có phần hiển thị "ghế đã chọn", cần xử lý trường hợp `flightSeat` là `null` cho toàn bộ hành khách adult/child (không chỉ riêng infant như trước).
