# UI-GUIDE-MultiCity: form tìm chuyến đa chặng (Phase C2)

Dành cho AI Agent làm UI. Backend (Phase C1) đã xong và có test, nhiệm vụ của bạn chỉ là làm form trên trang chủ gửi đúng dữ liệu.

## 1. Phạm vi file

Được sửa:
- `resources/views/home.blade.php`
- `resources/js/home.js`
- `resources/css/home.css` (chỉ khi cần style cho khối chặng thêm)

KHÔNG được đụng: controller, route, Form Request, service, test, layout, các view khác.

## 2. Hợp đồng dữ liệu (tên field cố định, không đổi)

Form hiện có (GET tới `flights.search.results`). Giữ nguyên mọi field hiện tại.

| Field | Ghi chú |
|---|---|
| `trip_type` | radio, giá trị `one-way` / `round-trip` / `multi-city` |
| `departure_airport_id`, `arrival_airport_id`, `departure_date` | chặng 1, giữ nguyên |
| `return_date` | chỉ dùng cho `round-trip`, giữ nguyên |
| `extra_legs[i][departure_airport_id]` | MỚI, điểm đi của chặng thêm thứ i |
| `extra_legs[i][arrival_airport_id]` | MỚI, điểm đến của chặng thêm thứ i |
| `extra_legs[i][date]` | MỚI, ngày bay (định dạng `YYYY-MM-DD`, ô `input type="date"`) |

Quy tắc về `i`:
- `i` bắt đầu từ 0 và liên tục (0, 1, 2), không được có lỗ hổng. Sau khi xóa một dòng phải đánh lại toàn bộ `name`.
- Tối đa 3 chặng thêm (tổng cộng 4 chặng với chặng 1). Tối thiểu 1 chặng thêm khi chọn `multi-city`.
- Mỗi chặng thêm có điểm đi RIÊNG. Điểm đi của chặng sau KHÔNG bắt buộc bằng điểm đến chặng trước (cho phép open-jaw). Không được ép hay khóa ô này.
- Mỗi chặng: điểm đi phải khác điểm đến. Ngày của chặng sau không được trước ngày chặng trước (ngày bằng nhau vẫn hợp lệ).

## 3. Hành vi cần làm

1. **Radio Đa chặng**: bỏ `disabled` và bỏ `title="Sắp ra mắt"` trên `#trip-multicity`, bỏ comment "mở ở giai đoạn C".
2. **Khi chọn `multi-city`**:
   - Ô "Ngày về" (`#return-date`) vẫn `disabled`, giá trị rỗng (đúng như logic `syncTripType` hiện tại: chỉ bật khi `round-trip`). Nên ẩn cả khối ô ngày về cho gọn.
   - Hiện khối `#extra-legs` chứa chặng 2 mặc định (đã có sẵn 1 dòng) và nút "+ Thêm chặng".
3. **Khi chọn `one-way` hoặc `round-trip`**: ẩn khối `#extra-legs` và đặt `disabled` cho MỌI input/select bên trong khối (kể cả hidden). Input `disabled` không được submit, nhờ đó `extra_legs` không bị gửi lên. Không được chỉ ẩn bằng CSS mà để input vẫn bật.
4. **Mỗi dòng chặng thêm gồm**:
   - Nhãn "Chặng N" (N = i + 2).
   - Bộ chọn điểm đi (popup tìm sân bay, giống bộ chọn chặng 1).
   - Bộ chọn điểm đến (popup tìm sân bay).
   - Ô ngày.
   - Nút xóa dòng (chặng 2 là dòng duy nhất thì ẩn nút xóa, vì multi-city cần tối thiểu 1 chặng thêm).
5. **Gợi ý điểm đi (chỉ gợi ý, không ép)**:
   - Khi tạo dòng mới: tự điền sẵn điểm đi bằng điểm đến của dòng liền trước (nếu dòng đó đã chọn).
   - Khi người dùng đổi điểm đến của chặng k: điền lại điểm đi chặng k+1 CHỈ NẾU người dùng chưa tự chọn ô đó. Đánh dấu bằng `data-touched="1"` trên bộ chọn điểm đi khi người dùng tự chọn bằng tay.
   - Người dùng luôn được sửa ô điểm đi sang sân bay khác.
6. **Nút "+ Thêm chặng"**: ẩn (hoặc disabled) khi đã đủ 3 chặng thêm.
7. **Nút xóa dòng**: xóa dòng, đánh lại chỉ số `i` liên tục cho các dòng còn lại (cập nhật `name` của cả 3 field, `id` các phần tử, nhãn "Chặng N"), rồi hiện lại nút "+ Thêm chặng".
8. **Ngày `min`**: ô ngày chặng k có `min` bằng ngày của chặng k-1 (chặng 2 lấy từ `#departure-date`; nếu chặng trước chưa có ngày thì lấy ngày hôm nay). Nếu giá trị hiện tại nhỏ hơn `min` thì đặt lại bằng `min`, giống hàm `syncReturnMin`. Cập nhật lại mỗi khi ngày chặng trước đổi hoặc dòng bị xóa. Dùng `localToday()` đã có, không dùng `toISOString()` trực tiếp.
9. **Khôi phục dữ liệu cũ khi validate lỗi**: backend redirect về trang chủ kèm `old()`. Hãy dựng lại các dòng từ `old('extra_legs')` (số dòng, điểm đi, điểm đến, ngày) và chọn lại radio `multi-city` (biến `$tripType` trong Blade đã hiểu `multi-city`? Hiện chỉ chấp nhận `one-way`/`round-trip`, cần thêm `multi-city` vào danh sách `in_array`). Gợi ý: gắn `data-old='@json(old("extra_legs", []))'` lên `#extra-legs` rồi đọc bằng JS.
10. **Hiển thị lỗi**: khối `$errors->any()` hiện có đã in mọi lỗi (kể cả `extra_legs.*`), không cần làm thêm. Có thể thêm class `is-invalid` cho ô tương ứng nếu muốn, không bắt buộc.

## 4. Gợi ý kỹ thuật (để dùng lại code có sẵn)

- `setupAirportPicker(type)` trong `home.js` dựng id từ tiền tố: `${type}-display`, `${type}-airport`, `${type}-dropdown-menu`, `${type}-search-input`. Mỗi bộ chọn mới chỉ cần markup đúng 4 id này rồi gọi `setupAirportPicker('<tiền tố>')`.
  - Tiền tố đề xuất: `extra-0-dep`, `extra-0-arr`, `extra-1-dep`, ... Id phải duy nhất trên trang.
  - Hàm này yêu cầu: input hiển thị (readonly, `data-bs-toggle="dropdown"`), input hidden, menu dropdown chứa ô tìm kiếm, và danh sách các phần tử có `data-id`, `data-code`, `data-city`.
- Để không render danh sách sân bay nhiều lần từ server, dùng một `<template>` cho dòng chặng và điền danh sách sân bay bằng cách sao chép `innerHTML` của `#departure-dropdown-menu .airport-list`. Các phần tử sân bay chỉ cần `data-id`, `data-code`, `data-city` (JS không dùng class `departure-airport-option`).
- Hidden input phải có `name` đúng hợp đồng (`extra_legs[i][departure_airport_id]`, `extra_legs[i][arrival_airport_id]`). Input hiển thị (readonly) KHÔNG có `name`, giống chặng 1.
- Khi tạo dòng bằng JS, các dropdown mới vẫn hoạt động nhờ Bootstrap data-API, nhưng nên đảm bảo mỗi bộ chọn nằm trong một `div.dropdown`.
- Bộ chọn điểm đi/đến ở dòng mới cũng cần được `setupAirportPicker` gắn lại sau khi đánh lại id (khi xóa dòng). Đơn giản nhất: xóa và dựng lại toàn bộ các dòng từ một mảng dữ liệu trong JS (`[{dep, arr, date, touched}]`), thay vì sửa id từng phần tử.
- Nút Swap (`#swap-airports`) chỉ áp dụng cho chặng 1, giữ nguyên. Nếu muốn, sau khi swap có thể gọi lại hàm gợi ý điểm đi chặng 2.
- Thiết kế mobile: mỗi dòng chặng xếp dọc trên màn hình nhỏ (dùng grid `col-12 col-md-*` như form hiện tại).

## 5. Không được làm

- Không đổi tên bất kỳ field nào ở mục 2.
- Không thêm validate ở JS thay cho backend (backend đã kiểm tra, chỉ cần `min` của ngày để dễ dùng).
- Không ép điểm đi chặng sau bằng điểm đến chặng trước, không khóa (readonly/disabled) ô điểm đi.
- Không cho tổng số chặng vượt 4, không cho gửi 0 chặng thêm khi chọn `multi-city`.
- Không sửa `trip_type` value, không đổi tên `extra_legs`.
- Không thêm thư viện JS mới. Chỉ dùng Bootstrap 5 và JS thuần như hiện tại.

## 6. Cách tự kiểm tra trước khi bàn giao

Chạy thủ công trên trình duyệt (cả desktop và màn hình hẹp):

1. Chọn Đa chặng, thêm 3 chặng thêm, chọn đủ điểm và ngày: bấm Tìm chuyến bay, phải vào được trang kết quả "Chặng 1/4".
2. Mở "Chặng 3" và thử xóa nó: các dòng còn lại đánh lại thứ tự, `name` là `extra_legs[0]`, `extra_legs[1]`, không có lỗ hổng (kiểm tra bằng DevTools hoặc xem query string URL trang kết quả).
3. Chọn Một chiều sau khi đã thêm chặng: URL trang kết quả KHÔNG chứa `extra_legs`.
4. Chọn Khứ hồi: `return_date` được gửi, `extra_legs` không được gửi.
5. Cố tình để điểm đi trùng điểm đến ở một chặng, hoặc ngày lùi: trang chủ hiện lỗi, và các dòng chặng thêm được khôi phục đúng như vừa nhập.
6. Thử open-jaw: chặng 2 đến Đà Nẵng, chặng 3 đi từ Phú Quốc. Form phải cho gửi, không báo lỗi.
7. Bấm "+ Thêm chặng" khi đã đủ 3 chặng thêm: không thêm được.

## 7. Bàn giao

Gửi lại `home.blade.php`, `home.js` (và `home.css` nếu có sửa) cho Tech Lead review. Kèm danh sách ngắn: những gì đã làm, những gì chưa làm hoặc làm khác hợp đồng.
