# UI-GUIDE-Passport: form hộ chiếu và cảnh báo thanh toán (Phase P-A3)

Dành cho AI Agent làm UI. Backend (P-A1, P-A2) đã xong và có test. Nhiệm vụ của bạn: hiển thị đúng dữ liệu backend truyền ra và gửi đúng tên field.

## 1. Phạm vi file

Được sửa:
- `resources/views/booking/passengers.blade.php`
- `resources/views/payment/show.blade.php`
- `resources/views/booking-history/show.blade.php` (chỉ ô "Giấy tờ tùy thân" của vé điện tử, xem mục 5)
- `resources/css/flights.css` nếu cần style

KHÔNG được đụng: controller, route, Form Request, service, model, test, layout, các view khác. Không thêm thư viện JS mới.

## 2. Dữ liệu backend đã truyền sẵn

### Trang nhập hành khách (`booking.passengers`)

| Biến | Kiểu | Ghi chú |
|---|---|---|
| `$isInternational` | bool | `true` khi có ít nhất một chặng khác quốc gia |
| `$countries` | array `mã ISO3 => tên tiếng Anh` | ví dụ `['VNM' => 'Vietnam', 'THA' => 'Thailand']` |
| `$pending` | array | như hiện tại (`adults`, `children`, `infants`, `legs`...) |

### Trang thanh toán (`payment.show`)

| Biến | Kiểu | Ghi chú |
|---|---|---|
| `$passportRisk['level']` | `null` / `'yellow'` / `'red'` | mức xấu nhất; `null` thì KHÔNG hiện khối cảnh báo |
| `$passportRisk['ref_date']` | `Y-m-d` | ngày hạ cánh của chặng quốc tế cuối |
| `$passportRisk['items']` | mảng | mỗi phần tử là hành khách có nguy cơ |
| `items[].passenger` | model `Passenger` | dùng `full_name`, `document_number`, `nationality_label` |
| `items[].level` | `'yellow'` / `'red'` | mức của từng hành khách |
| `items[].expiry_date` | `Y-m-d` | ngày hết hạn hộ chiếu |
| `items[].six_month_date` | `Y-m-d` | hết hạn trừ 6 tháng: ngày muộn nhất để hộ chiếu còn đủ 6 tháng |

### Accessor trên model `Passenger` (dùng ở mọi trang hiển thị)

- `$passenger->nationality_label` cho ra `Vietnam (VNM)`
- `$passenger->issued_country_label` cho ra `Vietnam (VNM)`
- `$passenger->gender_label` cho ra `Nam` hoặc `Nữ`

Mọi nơi hiển thị quốc gia phải dùng các accessor này, không tự tra mã quốc gia trong Blade.

## 3. `passengers.blade.php`

### 3.1 Khi `$isInternational` là `false`

Giữ nguyên toàn bộ như hiện tại. KHÔNG render bất kỳ field mới nào (backend sẽ bỏ qua nếu có).

### 3.2 Khi `$isInternational` là `true`

Hiện một banner thông tin ở đầu form: "Hành trình có chặng quốc tế: mọi hành khách (kể cả trẻ em và em bé) cần nhập thông tin hộ chiếu. Điền đúng như trên hộ chiếu, viết không dấu." Mọi field bên dưới đều `required`, dấu `*` đỏ ở nhãn.

Với mỗi hành khách thuộc nhóm `{group}` (`adults`, `children`, `infants`), chỉ số `i`, tên field CỐ ĐỊNH:

| Field (`name`) | Loại ô nhập | Ghi chú |
|---|---|---|
| `{group}[i][full_name]` | text | đã có; đổi hint thành "Như trên hộ chiếu, viết không dấu, ví dụ NGUYEN VAN A" |
| `{group}[i][document_type]` | hidden, giá trị `passport` | luôn gửi `passport`, không cho chọn loại khác |
| `{group}[i][document_number]` | text | đã có; nhãn đổi thành "Số hộ chiếu"; hint "6 đến 9 ký tự chữ và số, ví dụ C1234567" |
| `{group}[i][date_of_birth]` | date | đã có; `max` là ngày hôm nay |
| `{group}[i][gender]` | radio: `male` (Nam), `female` (Nữ) | MỚI |
| `{group}[i][nationality]` | select | MỚI, xem 3.3 |
| `{group}[i][document_issued_country]` | select | MỚI, xem 3.3 |
| `{group}[i][document_issue_date]` | date | MỚI, `max` là ngày hôm nay |
| `{group}[i][document_expiry_date]` | date | MỚI, không đặt `min` (backend xử lý, xem mục 6) |

Em bé vẫn giữ ô `infants[i][companion_adult_index]` như hiện tại. Nhãn "Số giấy khai sinh" và "Số giấy tờ (nếu có)" của em bé, trẻ em đổi thành "Số hộ chiếu" khi quốc tế.

### 3.3 Select quốc gia

- Duyệt `@foreach ($countries as $code => $name)`.
- Chữ hiển thị: `{{ $name }} ({{ $code }})`, giá trị `value="{{ $code }}"`.
- Option đầu là placeholder rỗng: "-- Chọn quốc gia --".
- Khôi phục `old("{group}.$i.nationality")` để `selected` đúng khi validate lỗi. Tuỳ chọn: nếu `old` trống thì chọn sẵn `VNM` cho hai ô này.

### 3.4 Lỗi validate

Dùng `@error("{group}.$i.<field>")` với class `is-invalid` như các ô hiện có. Khối `$errors->all()` ở đầu trang đã hiển thị toàn bộ lỗi, không cần làm thêm.

### 3.5 JS hỗ trợ (tuỳ chọn, chỉ để tiện cho người dùng)

Trong script có sẵn cuối file, khi người dùng rời ô (`blur`):
- Ô `full_name`: bỏ dấu tiếng Việt (`normalize("NFD")`, bỏ ký tự kết hợp, thay `đ` bằng `d`) rồi viết hoa.
- Ô `document_number`: bỏ ký tự không phải chữ hoặc số, viết hoa.

Backend vẫn chuẩn hóa lại ở server. JS không được chặn submit và không được xoá nội dung người dùng ngoài hai quy tắc trên.

## 4. `payment/show.blade.php`

Đặt một khối cảnh báo NGAY TRÊN form thanh toán (`#bf-payment-form`), chỉ hiển thị khi `$passportRisk['level']` khác `null`.

### 4.1 Màu và nội dung theo mức

Mỗi phần tử `items[]` là một dòng riêng, màu theo `items[].level`:

- **Vàng (`yellow`)**, dùng `alert alert-warning`: "Hộ chiếu của {full_name} còn hiệu lực đến {dd/mm/YYYY} nhưng chưa đủ 6 tháng kể từ ngày hạ cánh chặng quốc tế cuối ({ref_date dd/mm/YYYY}). Một số quốc gia và hãng hàng không có thể từ chối nhập cảnh hoặc từ chối cho lên máy bay."
- **Đỏ (`red`)**, dùng `alert alert-danger`: "Hộ chiếu của {full_name} hết hạn ngày {dd/mm/YYYY}, TRƯỚC khi hành trình quốc tế kết thúc ({ref_date dd/mm/YYYY}). Hành khách có nguy cơ cao bị từ chối khởi hành hoặc nhập cảnh."

Đầu khối ghi thêm một dòng chung: "Thông tin hộ chiếu có thể nhập sai hoặc vừa được gia hạn? Vui lòng liên hệ hotline 1900 1234 để được hỗ trợ cập nhật." (chưa có chức năng sửa trong hệ thống, không đặt link).

Định dạng ngày: `\Carbon\Carbon::parse(...)->format('d/m/Y')`.

### 4.2 Checkbox xác nhận (bắt buộc khi có cảnh báo)

Đặt trong form `#bf-payment-form`, ngay dưới khối cảnh báo:

```html
<div class="form-check my-3">
    <input class="form-check-input" type="checkbox" name="confirm_passport_risk" value="1" id="confirm_passport_risk" required>
    <label class="form-check-label" for="confirm_passport_risk">
        Tôi đã đọc và hiểu rủi ro về hộ chiếu nêu trên và vẫn muốn tiếp tục thanh toán.
    </label>
</div>
```

- Tên field CỐ ĐỊNH: `confirm_passport_risk`. Backend kiểm tra lại ở server mỗi lần thanh toán (kể cả thử lại sau khi thất bại), nên checkbox phải hiển thị lại mỗi lần trang tải, KHÔNG được tự tick sẵn.
- Khi `$passportRisk['level']` là `null`: KHÔNG render checkbox.
- Hai nút giả lập thanh toán giữ nguyên.
- Thuộc tính `required` làm trình duyệt chặn gửi form khi chưa tick.

## 5. `booking-history/show.blade.php`

Trong vé điện tử, ô "Giấy tờ tùy thân":
- Nếu `$ticket->passenger->document_type === 'passport'`: hiện "Hộ chiếu {document_number}", kèm một dòng nhỏ bên dưới `{nationality_label}` và "Hết hạn {dd/mm/YYYY}" (`document_expiry_date`).
- Ngược lại: giữ nguyên như hiện tại (`document_number` hoặc "Chưa cung cấp").

## 6. Không được làm

- Không đổi tên bất kỳ field nào ở mục 3 và mục 4.
- Không thêm `min` cho ô `document_expiry_date` (hộ chiếu hết hạn vẫn được gửi, backend chỉ cảnh báo ở bước thanh toán).
- Không hiện field Passport khi hành trình nội địa.
- Không tự tính mức cảnh báo ở Blade hay JS. Chỉ dùng `$passportRisk` do backend truyền.
- Không chặn nút thanh toán bằng cách khác ngoài `required` của checkbox.

## 7. Cách tự kiểm tra trước khi bàn giao

1. Hành trình nội địa (ví dụ SGN đến HAN): form hành khách giống hệt trước đây, không có ô hộ chiếu.
2. Hành trình quốc tế (cần có sân bay nước ngoài trong dữ liệu): mọi hành khách (kể cả trẻ em, em bé) có đủ ô hộ chiếu, dropdown hiện dạng "Vietnam (VNM)".
3. Gửi form thiếu ô: các lỗi hiện đúng ô, dữ liệu đã nhập được giữ lại (kể cả select và radio).
4. Nhập hộ chiếu hết hạn sau ngày bay khoảng 3 tháng: trang thanh toán hiện khối vàng và checkbox. Chưa tick thì không gửi được.
5. Nhập hộ chiếu hết hạn trước ngày bay: khối đỏ. Hộ chiếu còn hạn trên 6 tháng: không có khối cảnh báo và không có checkbox.
6. Thanh toán giả lập thất bại rồi thử lại: checkbox lại xuất hiện, chưa tick thì không gửi được.
7. Trang lịch sử đặt vé của đơn quốc tế: ô giấy tờ hiện "Hộ chiếu ..." cùng quốc tịch và ngày hết hạn.

## 8. Bàn giao

Gửi lại các file đã sửa cho Tech Lead review, kèm danh sách ngắn: đã làm gì, chưa làm hoặc làm khác hợp đồng.
