# GUIDE-UI.md - Giai đoạn B (khứ hồi): chỉnh giao diện khớp backend

Dự án: booking-flight (Laravel 11, Blade, Bootstrap 5 CDN, JS thuần, Vite theo từng trang).
Người giao việc: Namv (BE + Git lead). Agent chỉ làm giao diện.

## 0. Quy tắc cứng

1. Chỉ sửa Blade, CSS, JS. KHÔNG sửa Controller, Model, Service, FormRequest, routes, migrations, tests.
2. KHÔNG đổi tên bất kỳ `name`, `id`, `value`, tên route, tên biến Blade ghi trong tài liệu này. Thiếu biến thì ghi vào mục "Yêu cầu BE" trong báo cáo, không tự thêm query hay logic vào Blade.
3. Hai ô ngày giữ `type="date"` (giá trị luôn `Y-m-d`). Không dùng Flatpickr cho hai ô này.
4. Giữ nguyên cách hiển thị giờ hiện tại: `\Carbon\Carbon::parse($flight->departure_time)`. Đổi múi giờ hiển thị là việc khác, không làm ở B3.
5. Không thêm thư viện. CSS mới đặt vào `resources/css/flights.css` (trang booking) hoặc `resources/css/home.css` (trang chủ), tiền tố `bf-`. Không sửa class `bf-*` đã có.
6. Mọi chữ hiển thị là tiếng Việt có dấu. Comment ngắn, không kẻ đường dài.
7. Test cũ fail vì đổi chữ hiển thị: KHÔNG tự sửa test, ghi vào báo cáo để Namv xử lý.

## 1. File được phép sửa

| File | Việc |
|--|--|
| `resources/views/home.blade.php` | Gửi `trip_type`, `return_date`; khóa radio Đa chặng |
| `resources/js/home.js` | Bật/tắt ngày về, `min` ngày về, khôi phục form |
| `resources/css/home.css` | Style radio bị khóa |
| `resources/views/flights/results.blade.php` | Nhãn "Chặng k/N", tóm tắt chặng đã chọn, form precheck mới |
| `resources/views/booking/passengers.blade.php` | Sidebar nhiều chặng |
| `resources/views/payment/show.blade.php` | Sidebar nhiều chặng, vé nhóm theo chặng |
| `resources/views/booking-history/index.blade.php` | Hành trình nhiều chặng |
| `resources/views/booking-history/show.blade.php` | Nhãn chặng, thứ tự chặng, sửa badge thanh toán |
| `resources/css/flights.css` | Style bổ sung (nếu cần) |

Không đụng: `payment.js` và toàn bộ file PHP.

## 2. Hợp đồng BE (đã chốt, đã có test)

### 2.1 Tên route

| Tên route | Method | URL | Auth |
|--|--|--|--|
| `home` | GET | `/` | không |
| `flights.search.results` | GET | `/flights/results` | không |
| `booking.precheck` | POST | `/booking/precheck` | có |
| `booking.leg` | GET | `/booking/leg/{leg}` (leg = 1, 2...) | có |
| `booking.passengers.form` | GET | `/booking/passengers` | có |
| `booking.passengers.store` | POST | `/booking/passengers` | có |
| `payment.show` | GET | `/payment/{booking}` | có |
| `payment.store` | POST | `/payment/{booking}` | có |
| `booking-history.index` | GET | | có |
| `booking-history.show` | GET | | có |

Không tự ghép URL chặng bằng tay. Dùng `$backUrl` do BE truyền.

### 2.2 Form tìm kiếm trang chủ (GET `flights.search.results`)

| name | id | kiểu | giá trị | ghi chú |
|--|--|--|--|--|
| `trip_type` | `trip-oneway` | radio | `one-way` | mặc định checked |
| `trip_type` | `trip-roundtrip` | radio | `round-trip` | |
| `trip_type` | `trip-multicity` | radio | `multi-city` | **disabled** ở giai đoạn B |
| `departure_airport_id` | `departure-airport` | hidden | id sân bay | |
| `arrival_airport_id` | `arrival-airport` | hidden | id sân bay | |
| `departure_date` | `departure-date` | date | `Y-m-d` | bắt buộc, >= hôm nay |
| `return_date` | `return-date` | date | `Y-m-d` | **MỚI**: chỉ gửi khi khứ hồi, bắt buộc khi khứ hồi, >= `departure_date` |
| `fare_class_id` | | select | id | |
| `adults` / `children` / `infants` | `adults-input` / `children-input` / `infants-input` | number | số nguyên | giữ nguyên |

Ô ngày về khi không phải khứ hồi phải `disabled` (input disabled không được gửi, URL không có `return_date`).

### 2.3 Form chọn chuyến (POST `booking.precheck`) trong `results.blade.php`

CHỈ gồm 3 input:

```blade
@csrf
<input type="hidden" name="flight_id" value="{{ $flight->id }}">
<input type="hidden" name="leg" value="{{ $legIndex }}">
```

TUYỆT ĐỐI không thêm lại `fare_class_id`, `adults`, `children`, `infants`. BE lấy các giá trị này từ session. Client gửi thêm sẽ bị bỏ qua và test sẽ phát hiện.

### 2.4 Form hành khách (POST `booking.passengers.store`): GIỮ NGUYÊN

`adults[i][full_name|document_number|date_of_birth]`, `children[i][...]` tương tự, `infants[i][...]` kèm `infants[i][companion_adult_index]`. Id nút submit `bf-btn-submit-passengers`, form `bf-passenger-form`.

### 2.5 Form thanh toán (POST `payment.store`) và `payment.js`: GIỮ NGUYÊN

`name="method"` (value `the_tin_dung` hoặc `vi_dien_tu`), `name="simulate_result"` (value `success` hoặc `failed`). Các id/data mà `payment.js` dùng: `payment-expiry-box` (+ `data-expires-at`, `data-server-now`, `data-redirect-url`), `payment-countdown-badge`, `paymentExpiredModal`, `btn-close-expired-modal`, `redirect-seconds`, `btn-modal-redirect-home`.

### 2.6 Flash và lỗi

`session('error')`, `session('status')`, `$errors`. Các trang booking đã render sẵn, giữ nguyên khối alert.

### 2.7 Giá trị enum (dùng đúng chữ, không đoán)

- `bookings.status`: `pending`, `paid`, `cancelled`
- `payments.status`: `pending`, `success`, `failed`
- `payments.method`: `the_tin_dung`, `vi_dien_tu`
- `passengers.passenger_type`: `adult`, `child`, `infant`
- `flights.status`: `scheduled`, `delayed`, `cancelled`

## 3. Biến Blade có sẵn theo từng view

**`flights/results.blade.php`**

| Biến | Kiểu | Ghi chú |
|--|--|--|
| `$flights` | Paginator\<Flight\> | mỗi Flight có thêm `min_price`, `available_seats`; đã load `aircraft.airline`, `departureAirport`, `arrivalAirport` |
| `$departureAirport`, `$arrivalAirport` | Airport | `iata_code`, `city` |
| `$fareClass` | FareClass | `name` |
| `$departureDate` | string `Y-m-d` | ngày local của CHẶNG đang xem |
| `$adults`, `$children`, `$infants` | int | |
| `$legIndex` | int | 0-based, 0 = chặng đầu |
| `$totalLegs` | int | 1 (một chiều) hoặc 2 (khứ hồi) |
| `$chosenFlights` | Collection\<Flight\> | các chuyến đã chọn ở chặng trước, rỗng khi `$legIndex = 0` |
| `$backUrl` | ?string | URL chọn lại chặng trước, `null` khi `$legIndex = 0` |

**`booking/passengers.blade.php`**: `$flight` (chuyến đầu, giữ cho `@section('title')`), `$flights` (Collection\<Flight\> sắp theo giờ cất cánh, đã load `departureAirport`, `arrivalAirport`, `aircraft.airline`), `$pending` (mảng: `trip_type` là `one-way` hoặc `round-trip`, `fare_class_id`, `adults`, `children`, `infants`, `legs[]`).

**`payment/show.blade.php`**: `$booking`, `$expiresAt` (Carbon). Chặng: dùng `$booking->ordered_legs`.

**`booking-history/index.blade.php`**: `$bookings` (paginator). Mỗi booking dùng `$booking->ordered_legs`.

**`booking-history/show.blade.php`**: `$booking`. Chặng: `$booking->ordered_legs`.

**`home.blade.php`**: `$airports`, `$fareClasses`.

`$booking->ordered_legs` là Collection\<BookingFlight\> đã sắp theo `departure_time` của chuyến (chặng đi trước, chặng về sau). KHÔNG dùng `$booking->bookingFlights` để hiển thị thứ tự (không đảm bảo thứ tự). Mỗi phần tử có `->flight` và `->tickets`.

## 4. Quy ước nhãn chặng (dùng thống nhất ở mọi view)

- Một chặng: không hiện nhãn nào.
- Khứ hồi: chặng đầu "Chuyến đi", chặng sau "Chuyến về".
- Nhiều chặng khác khứ hồi (giai đoạn C): "Chặng k".

Trước khi đặt vé (results, passengers): khứ hồi khi `$pending['trip_type'] === 'round-trip'`.

Sau khi đặt vé (payment, history): không có cột `trip_type`, suy ra bằng công thức chuẩn:

```blade
@php
    $legs = $booking->ordered_legs;
    $legCount = $legs->count();
    $firstFlight = $legs->first()?->flight;
    $lastFlight = $legs->last()?->flight;
    $isRoundTrip = $legCount === 2 && $firstFlight && $lastFlight
        && $firstFlight->departure_airport_id === $lastFlight->arrival_airport_id
        && $firstFlight->arrival_airport_id === $lastFlight->departure_airport_id;
@endphp
{{-- trong vòng lặp @foreach ($legs as $i => $bf) --}}
@php
    $legLabel = $legCount > 1
        ? ($isRoundTrip ? ($i === 0 ? 'Chuyến đi' : 'Chuyến về') : 'Chặng '.($i + 1))
        : null;
@endphp
```

Nhãn hiển thị: `<span class="badge bg-primary">{{ $legLabel }}</span>` (chỉ khi `$legLabel` khác null).

## 5. Việc cần làm theo từng file

### 5.1 `home.blade.php`

a) Ngay trên khối radio, thêm:

```blade
@php
    $tripType = in_array(old('trip_type'), ['one-way', 'round-trip'], true) ? old('trip_type') : 'one-way';
@endphp
```

b) Thay 3 radio. Đa chặng bị khóa:

```blade
<input type="radio" class="btn-check" name="trip_type" id="trip-oneway" value="one-way" {{ $tripType === 'one-way' ? 'checked' : '' }}>
<label class="bf-tab-btn" for="trip-oneway">Một chiều</label>

<input type="radio" class="btn-check" name="trip_type" id="trip-roundtrip" value="round-trip" {{ $tripType === 'round-trip' ? 'checked' : '' }}>
<label class="bf-tab-btn" for="trip-roundtrip">Khứ hồi</label>

{{-- Đa chặng: mở ở giai đoạn C --}}
<input type="radio" class="btn-check" name="trip_type" id="trip-multicity" value="multi-city" disabled>
<label class="bf-tab-btn" for="trip-multicity" title="Sắp ra mắt">Đa chặng</label>
```

c) Thay ô ngày về (thêm `name`, `value`, trạng thái ban đầu theo `$tripType`):

```blade
<input type="date" class="form-control bf-input bf-date-input @error('return_date') is-invalid @enderror"
       name="return_date" id="return-date" value="{{ old('return_date') }}"
       {{ $tripType === 'round-trip' ? 'required' : 'disabled' }}>
```

d) Nên làm: giữ lại số hành khách khi form render lại do lỗi:
`value="{{ old('adults', 1) }}"`, `value="{{ old('children', 0) }}"`, `value="{{ old('infants', 0) }}"` ở 3 input `adults-input`, `children-input`, `infants-input`.

### 5.2 `home.css`

```css
/* Radio Đa chặng bị khóa */
.btn-check:disabled + .bf-tab-btn {
    opacity: 0.5;
    cursor: not-allowed;
}
```

### 5.3 `home.js`

a) Thay phần "1. KHỞI TẠO BIẾN & THIẾT LẬP NGÀY TỐI THIỂU" (từ dòng `const today` đến hết khối `tripTypeInputs.forEach`) bằng:

```js
// Ngày hôm nay theo giờ máy người dùng (toISOString trả về ngày UTC, có thể lệch 1 ngày)
function localToday() {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().split("T")[0];
}
const today = localToday();
if (departureDate) departureDate.min = today;

// Ô ngày về chỉ bật khi chọn Khứ hồi (input disabled không được gửi lên server)
function syncTripType() {
    const checked = document.querySelector('input[name="trip_type"]:checked');
    const isRound = !!checked && checked.value === "round-trip";
    if (!returnDate) return;
    returnDate.disabled = !isRound;
    returnDate.required = isRound;
    if (!isRound) returnDate.value = "";
}

// Ngày về không được trước ngày đi
function syncReturnMin() {
    if (!returnDate) return;
    const min = departureDate && departureDate.value ? departureDate.value : today;
    returnDate.min = min;
    if (returnDate.value && returnDate.value < min) returnDate.value = min;
}

tripTypeInputs.forEach(function (input) {
    input.addEventListener("change", syncTripType);
});
if (departureDate) departureDate.addEventListener("change", syncReturnMin);
syncTripType();
syncReturnMin();
```

b) Cuối hàm `setupAirportPicker(type)` (trước dấu `}` đóng hàm), khôi phục chữ hiển thị khi form render lại với `old()`:

```js
// Hidden đã có id (old) nhưng ô hiển thị đang trống: lấy chữ từ option tương ứng
if (inputHidden && inputHidden.value && !inputDisplay.value) {
    const saved = dropdownMenu.querySelector(`[data-id="${inputHidden.value}"]`);
    if (saved) {
        const code = saved.getAttribute("data-code") || "";
        const city = saved.getAttribute("data-city") || "";
        inputDisplay.value = code ? `${city} (${code})` : city;
    }
}
```

c) Cuối mục "3. QUẢN LÝ SỐ LƯỢNG HÀNH KHÁCH", gọi một lần `updatePassengerSummary();` để chữ tóm tắt đúng khi có `old()`.

### 5.4 `flights/results.blade.php`

a) Form chọn chuyến: xem mục 2.3 (xóa 4 input cũ, thêm `leg`).

b) Trong `<section class="bf-summary-bar">`, ngay trước `<div class="bf-summary-route">`:

```blade
@if ($totalLegs > 1)
    <div class="mb-2">
        <span class="badge bg-primary">Chặng {{ $legIndex + 1 }}/{{ $totalLegs }}</span>
        <span class="fw-semibold small ms-1">{{ $legIndex === 0 ? 'Chuyến đi' : 'Chuyến về' }}</span>
    </div>
@endif
```

c) Ngay sau `</section>` của thanh tóm tắt, trước `<div class="row g-4">`:

```blade
@if ($chosenFlights->isNotEmpty())
    <div class="alert alert-light border mb-4">
        <div class="fw-bold small text-muted text-uppercase mb-2">Chuyến bạn đã chọn</div>
        @foreach ($chosenFlights as $i => $cf)
            @php $cfDep = \Carbon\Carbon::parse($cf->departure_time); @endphp
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 {{ ! $loop->last ? 'mb-2' : '' }}">
                <div>
                    <span class="badge bg-secondary me-1">Chặng {{ $i + 1 }}</span>
                    <strong>{{ $cf->departureAirport->iata_code }} → {{ $cf->arrivalAirport->iata_code }}</strong>
                    <span class="text-muted small ms-2">
                        {{ $cfDep->format('H:i d/m/Y') }} · {{ $cf->aircraft->airline->name ?? '' }}
                    </span>
                </div>
            </div>
        @endforeach
        <a href="{{ $backUrl }}" class="small text-decoration-none">Chọn lại chuyến trước</a>
    </div>
@endif
```

d) Trạng thái không có kết quả (`@empty`): với chặng sau, đổi nội dung thành "không có chuyến khởi hành sau khi chặng trước hạ cánh" và nút quay về `$backUrl`:

```blade
@if ($legIndex > 0)
    <p class="bf-empty-desc">
        Không có chuyến bay nào từ <strong>{{ $departureAirport->city }} ({{ $departureAirport->iata_code }})</strong>
        đến <strong>{{ $arrivalAirport->city }} ({{ $arrivalAirport->iata_code }})</strong>
        trong ngày {{ $depDate->format('d/m/Y') }} khởi hành sau khi chặng trước hạ cánh,
        với hạng vé <strong>{{ $fareClass->name }}</strong>.
    </p>
    <a href="{{ $backUrl }}" class="bf-btn-empty-search">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Chọn lại chuyến của chặng trước
    </a>
@else
    {{-- giữ nguyên đoạn mô tả và nút "Tìm kiếm ngày khác" hiện có --}}
@endif
```

Chặng một chiều (`$totalLegs = 1`) phải hiển thị y hệt hiện tại (không badge, không khối "đã chọn").

### 5.5 `booking/passengers.blade.php`

Chỉ sửa cột phải (`<aside>`). Cột trái (form) KHÔNG đổi. Giữ nguyên `@section('title')`. Thay toàn bộ nội dung `<aside>`:

```blade
<aside class="col-lg-4 col-md-5">
    @php
        $isRound = ($pending['trip_type'] ?? 'one-way') === 'round-trip';
        $legCount = $flights->count();
        $seatsNeeded = $pending['adults'] + ($pending['children'] ?? 0);
    @endphp

    <div class="bf-booking-sidebar">
        <h3 class="bf-booking-sidebar__title">
            <i class="bi bi-ticket-detailed-fill text-primary" aria-hidden="true"></i>
            <span>Tóm tắt hành trình</span>
        </h3>

        @foreach ($flights as $i => $f)
            @php
                {{-- copy đúng cách tính $depTime, $arrTime, $durationText của bản cũ, thay $flight bằng $f --}}
                $airlineName = $f->aircraft->airline->name ?? 'Hãng hàng không';
                $aircraftModel = $f->aircraft->model ?? 'Aircraft';
                $legLabel = $legCount > 1
                    ? ($isRound ? ($i === 0 ? 'Chuyến đi' : 'Chuyến về') : 'Chặng '.($i + 1))
                    : null;
            @endphp

            <div class="{{ ! $loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                @if ($legLabel)
                    <div class="mb-2"><span class="badge bg-primary">{{ $legLabel }}</span></div>
                @endif

                {{-- giữ nguyên các khối: bf-sidebar-route, bf-sidebar-airline, 3 dòng meta (Cất cánh, Hạ cánh, Thời gian bay), dùng $f --}}
            </div>
        @endforeach

        {{-- Giữ dòng "Số lượng khách" như cũ, rồi thay dòng "Số ghế cần đặt": --}}
        <div class="bf-sidebar-meta-row">
            <span>Số ghế cần đặt:</span>
            <strong class="text-primary">
                {{ $seatsNeeded }} ghế riêng{{ $legCount > 1 ? ' mỗi chặng (tổng '.($seatsNeeded * $legCount).' ghế)' : '' }}
            </strong>
        </div>

        <div class="bf-sidebar-security-badge">
            <i class="bi bi-shield-check text-success" aria-hidden="true"></i>
            <span>
                {{ $legCount > 1
                    ? 'Ghế của tất cả các chặng được giữ cùng lúc ngay sau khi bạn xác nhận; nếu một chặng hết ghế, đơn sẽ không được tạo.'
                    : 'Ghế sẽ được hệ thống giữ tự động ngay sau khi bạn xác nhận thông tin hành khách.' }}
            </span>
        </div>
    </div>
</aside>
```

Sửa chữ trong thông báo em bé: "Em bé ngồi cùng người lớn đi kèm và không chiếm ghế riêng. Giá vé bằng 10% giá ghế của người lớn đi kèm trên mỗi chặng."

### 5.6 `payment/show.blade.php`

Chỉ sửa `<aside>`. Phần form, banner đếm ngược, modal hết hạn KHÔNG đổi.

a) Thay khối `@php` đầu `<aside>` bằng:

```blade
@php
    $legs = $booking->ordered_legs;
    $legCount = $legs->count();
    $firstFlight = $legs->first()?->flight;
    $lastFlight = $legs->last()?->flight;
    $isRoundTrip = $legCount === 2 && $firstFlight && $lastFlight
        && $firstFlight->departure_airport_id === $lastFlight->arrival_airport_id
        && $firstFlight->arrival_airport_id === $lastFlight->departure_airport_id;
    $totalTicketsCount = $legs->sum(fn ($bf) => $bf->tickets->count());
@endphp
```

b) Thay khối `@if ($flight) ... @endif` (route, hãng, giờ cất cánh/hạ cánh/thời gian bay) bằng vòng lặp trên `$legs`, mỗi chặng một khối nhỏ có nhãn (mục 4):

```blade
@foreach ($legs as $i => $bf)
    @php
        $f = $bf->flight;
        {{-- legLabel theo mục 4; $depTime, $arrTime, $durationText, $airlineName, $aircraftModel,
             $departureAirport, $arrivalAirport tính từ $f như bản cũ, giữ ?-> --}}
    @endphp
    @if ($f)
        <div class="{{ ! $loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
            @if ($legLabel) <div class="mb-2"><span class="badge bg-primary">{{ $legLabel }}</span></div> @endif
            {{-- giữ nguyên các khối bf-sidebar-route, bf-sidebar-airline, 3 dòng meta của bản cũ --}}
        </div>
    @endif
@endforeach
```

c) Danh sách vé: gom theo chặng. Thay hai `@foreach` lồng nhau:

```blade
@foreach ($legs as $i => $bf)
    @if ($legCount > 1)
        <div class="small fw-bold text-navy mt-2 mb-1">
            {{ $isRoundTrip ? ($i === 0 ? 'Chuyến đi' : 'Chuyến về') : 'Chặng '.($i + 1) }}
            · {{ $bf->flight?->departureAirport?->iata_code }} → {{ $bf->flight?->arrivalAirport?->iata_code }}
        </div>
    @endif
    @foreach ($bf->tickets as $ticket)
        {{-- giữ nguyên khối bf-ticket-row của bản cũ --}}
    @endforeach
    @if ($legCount > 1)
        <div class="d-flex justify-content-between small text-muted mb-2">
            <span>Tạm tính chặng này</span>
            <span>{{ number_format($bf->tickets->sum('price'), 0, ',', '.') }} đ</span>
        </div>
    @endif
@endforeach
```

d) Khối "Tổng thanh toán" giữ nguyên (`$booking->total_amount`).

### 5.7 `booking-history/index.blade.php`

Thay khối `@php` đầu vòng lặp `@foreach ($bookings as $booking)`:

```blade
@php
    $legs = $booking->ordered_legs;
    $firstFlight = $legs->first()?->flight;
    $lastFlight = $legs->last()?->flight;
    $depTime = $firstFlight ? \Carbon\Carbon::parse($firstFlight->departure_time) : null;
    $retTime = $legs->count() > 1 && $lastFlight ? \Carbon\Carbon::parse($lastFlight->departure_time) : null;

    // Chuỗi sân bay: điểm đi chặng đầu rồi điểm đến từng chặng (SGN → HAN → SGN)
    $codes = [];
    $cities = [];
    if ($firstFlight) {
        $codes[] = $firstFlight->departureAirport?->iata_code ?? $firstFlight->departure_airport_id;
        $cities[] = $firstFlight->departureAirport?->city;
        foreach ($legs as $bf) {
            $codes[] = $bf->flight->arrivalAirport?->iata_code ?? $bf->flight->arrival_airport_id;
            $cities[] = $bf->flight->arrivalAirport?->city;
        }
    }
    $isRoundTrip = $legs->count() === 2 && $firstFlight && $lastFlight
        && $firstFlight->departure_airport_id === $lastFlight->arrival_airport_id
        && $firstFlight->arrival_airport_id === $lastFlight->departure_airport_id;
@endphp
```

Cột "Hành trình bay":

```blade
<td>
    @if ($firstFlight)
        <div class="fw-bold text-navy">
            {{ implode(' → ', $codes) }}
            @if ($isRoundTrip)
                <span class="badge bg-info-subtle text-info-emphasis ms-1">Khứ hồi</span>
            @elseif ($legs->count() > 1)
                <span class="badge bg-info-subtle text-info-emphasis ms-1">{{ $legs->count() }} chặng</span>
            @endif
        </div>
        <div class="text-muted small" style="font-size: 0.76rem;">{{ implode(' → ', array_filter($cities)) }}</div>
    @else
        <span class="text-muted fst-italic">Không có dữ liệu</span>
    @endif
</td>
```

Cột "Ngày bay":

```blade
<td>
    @if ($depTime)
        <div class="fw-bold text-navy">{{ $depTime->format('H:i') }}</div>
        <div class="text-muted small" style="font-size: 0.76rem;">{{ $depTime->format('d/m/Y') }}</div>
        @if ($retTime)
            <div class="text-muted small" style="font-size: 0.76rem;">
                {{ $isRoundTrip ? 'Về' : 'Chặng cuối' }}: {{ $retTime->format('d/m/Y') }}
            </div>
        @endif
    @else
        <span class="text-muted">-</span>
    @endif
</td>
```

Các cột còn lại giữ nguyên. Nếu file này đã được dán snippet cũ từ Namv, chỉ cần đổi công thức `$isRoundTrip` cho khớp bản trên.

### 5.8 `booking-history/show.blade.php`

a) Thứ tự và nhãn chặng. Đầu khối `<main>`:

```blade
@php
    {{-- khối $legs, $legCount, $firstFlight, $lastFlight, $isRoundTrip theo mục 4 --}}
@endphp
```

Đổi `@foreach ($booking->bookingFlights as $bf)` thành `@foreach ($legs as $i => $bf)`, thêm `$legLabel` (mục 4) trong `@php` của vòng lặp. Trong header của hai thẻ (thẻ hành trình và thẻ vé điện tử), thêm nhãn trước tiêu đề:

```blade
@if ($legLabel) <span class="badge bg-primary me-2">{{ $legLabel }}</span> @endif
```

Tiêu đề thẻ vé điện tử giữ "Vé điện tử hành khách (N vé)".

b) Sửa badge "Thành công" luôn hiện (COMMIT RIÊNG, mục 6). Trong thẻ "Chi tiết giao dịch thanh toán", thay `<span class="badge bg-success text-white">...Thành công</span>` bằng:

```blade
@php
    [$payBadgeClass, $payBadgeIcon, $payBadgeText] = match ($booking->payment->status) {
        'success' => ['bg-success text-white', 'bi-check-all', 'Thành công'],
        'failed' => ['bg-danger text-white', 'bi-x-circle', 'Thất bại'],
        default => ['bg-warning text-dark', 'bi-clock', 'Đang xử lý'],
    };
@endphp
<span class="badge {{ $payBadgeClass }}">
    <i class="bi {{ $payBadgeIcon }} me-1"></i>{{ $payBadgeText }}
</span>
```

Mã giao dịch có thể `null` khi chưa/không thành công: hiển thị `{{ $booking->payment->transaction_code ?: '-' }}`.

## 6. Cách tách commit (mỗi commit tự chạy được)

1. `feat(home): gửi trip_type và return_date, khóa Đa chặng` (home.blade, home.js, home.css)
2. `feat(booking): giao diện chọn chuyến và hành khách nhiều chặng` (results, passengers, flights.css)
3. `feat(booking): thanh toán và lịch sử hiển thị nhiều chặng` (payment/show, history index, history show: nhãn và thứ tự, KHÔNG gồm sửa badge)
4. `fix(history): badge thanh toán theo payments.status` (chỉ mục 5.8b)

Trước mỗi commit chạy `npm run build`, `php artisan test`, `vendor/bin/pint --test`.

## 7. Checklist kiểm tay (báo kết quả từng dòng)

1. Một chiều chạy hết luồng, giao diện giống trước: không badge chặng, không khối "đã chọn".
2. Khứ hồi: ngày về bị chặn trước ngày đi bằng `min`; đổi ngày đi sau ngày về thì ngày về tự nhảy theo.
3. Radio Đa chặng mờ và không bấm được; mở URL `?trip_type=multi-city` thì trang chủ báo lỗi và form về Một chiều.
4. Sau lỗi validate, trang chủ giữ đúng sân bay, ngày, số khách, loại hành trình.
5. Chặng 2: thanh tóm tắt "Chặng 2/2 · Chuyến về", khối chuyến đã chọn, "Chọn lại chuyến trước" hoạt động, không có kết quả thì hiện đúng trạng thái trống.
6. Trang hành khách: sidebar hai chặng, nhãn đúng, số ghế "mỗi chặng (tổng N)".
7. Trang thanh toán: vé nhóm theo chặng, chặng đi trước chặng về kể cả khi hai `booking_flights` tạo ngược thứ tự; tổng tiền không đổi.
8. Lịch sử: danh sách hiện `SGN → HAN → SGN`, nhãn Khứ hồi, hai ngày bay; chi tiết hiện đúng thứ tự và nhãn.
9. Badge thanh toán đủ 3 trạng thái `success`, `failed`, `pending`.
10. In (Ctrl+P) đơn khứ hồi đã thanh toán: thấy cả hai chặng và vé.
11. Màn hình 375px không vỡ.
12. Khách chưa đăng nhập bấm "Chọn chuyến" rồi đăng nhập xong: ghi nhận hành vi (có thể lỗi 405, đó là việc của BE, không sửa ở UI).

## 8. Báo cáo trả về

Liệt kê file đã sửa, kết quả `npm run build` / `php artisan test` / `vendor/bin/pint --test`, test nào đỏ do đổi chữ hiển thị, và mục "Yêu cầu BE" (biến còn thiếu nếu có).