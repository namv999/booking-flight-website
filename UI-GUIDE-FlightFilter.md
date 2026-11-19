# UI-GUIDE: Bộ lọc & sắp xếp trang kết quả chuyến bay

Phạm vi sửa: resources/views/flights/results.blade.php, resources/js/flights.js, resources/css/flights.css.
Cấm sửa: PHP (controller, service, request, routes), form POST booking.precheck (hidden flight_id, leg), phần phân trang.

## 1. Nguyên tắc
- Lọc và sắp xếp chạy HOÀN TOÀN ở server qua GET query string. JS không được ẩn/hiện card hay đếm số chuyến.
- XÓA logic lọc DOM trong flights.js (applyFilters, applySorting, activeTimeSlots, hourMatchesSlot).
- XÓA các thuộc tính data-airline, data-price, data-dep-hour, data-dep-time, data-stops trên .bf-flight-card.
- XÓA khối "Số điểm dừng" (checkbox #bf-filter-direct, nhãn "Ưu tiên"). Mọi chuyến đều bay thẳng, không có dữ liệu để lọc.
- Số lượng hiển thị: dùng {{ $flights->total() }}, bỏ id bf-count-display nếu JS không còn dùng.

## 2. Biến Blade có sẵn (đừng tự tính lại)
- $filters: array [airlines[], price_min|null, price_max|null, dep_time[], arr_time[], sort]
- $facets: array [total, airlines (collection các phần tử code, name, count), price_min|null, price_max|null]
  (thống kê trên kết quả CHƯA lọc, dùng để vẽ danh sách hãng và placeholder giá)
- $hasActiveFilters: bool
- $timeSlotOptions: [slotKey => [label, from, to]] (4 khung, vòng lặp để vẽ nút giờ, đừng hardcode)
- $sortOptions: [sortKey => label]
- $filterAction: URL submit của form lọc, cũng là URL của nút "Đặt lại" (không kèm query)
- $flights: paginator, $flights->total() là số chuyến sau lọc

## 3. Form lọc
- Một <form id="bf-filter-form" method="GET" action="{{ $filterAction }}"> bọc toàn bộ sidebar. KHÔNG thêm @csrf (GET).
- Mọi control ngoài sidebar (sort ở toolbar) gắn vào form bằng thuộc tính form="bf-filter-form".
- KHÔNG gửi input name="page". Đổi bộ lọc thì tự về trang 1.

| Control | name | value | Trạng thái |
|---|---|---|---|
| Checkbox hãng (lặp $facets['airlines']) | airlines[] | $al['code'] | checked nếu in_array($al['code'], $filters['airlines']) |
| Hiển thị mỗi hãng | | | tên + badge mã + (count) |
| Giá tối thiểu (input number) | price_min | số nguyên thuần, không dấu chấm | value = $filters['price_min'], placeholder = $facets['price_min'] |
| Giá tối đa (input number) | price_max | số nguyên thuần | value = $filters['price_max'], placeholder = $facets['price_max'] |
| Nút khung giờ đi (lặp $timeSlotOptions) | dep_time[] | slotKey | checkbox ẩn + label dạng nút, checked theo $filters['dep_time'] |
| Nút khung giờ đến (lặp $timeSlotOptions) | arr_time[] | slotKey | tương tự, nhóm riêng "Giờ hạ cánh" |
| Sắp xếp (lặp $sortOptions) | sort | sortKey | radio dạng pill, checked nếu = $filters['sort'] |

- Nút khung giờ phải là <input type="checkbox"> thật + <label> (đổi class .bf-time-slot-btn thành style cho label), để bàn phím và screen reader hoạt động. Bỏ role="button" tabindex="0" trên div.
- Nút "Đặt lại": <a href="{{ $filterAction }}">, KHÔNG dùng javascript:void(0), KHÔNG xử lý bằng JS. Chỉ hiện khi $hasActiveFilters hoặc $filters['sort'] !== 'price_asc'.
- Nút "Áp dụng" (submit) cho nhóm giá. Giá cũng submit khi nhấn Enter.

## 4. Hành vi JS (flights.js, rất nhỏ)
- Checkbox hãng, khung giờ, radio sort: sự kiện change gọi form.requestSubmit().
- Input giá: KHÔNG submit khi gõ, chỉ submit khi Enter hoặc bấm "Áp dụng".
- Trước khi submit, bỏ name của input rỗng (hoặc để Laravel tự coi rỗng là null, không cần xử lý thêm).
- Không fetch/AJAX. Trang reload bình thường (trạng thái nằm trên URL nên nút Back và chia sẻ link hoạt động).

## 5. Toolbar kết quả
- Hiện "Tìm thấy N chuyến bay phù hợp" với N = $flights->total().
- Pill sort ở toolbar HIỆN CẢ TRÊN MOBILE (hiện đang d-none d-sm-flex). Trên mobile, đưa toàn bộ sidebar vào Bootstrap offcanvas mở bằng nút "Bộ lọc" (kèm badge số nhóm đang lọc nếu có).
- Có thể hiện chip các bộ lọc đang bật, mỗi chip là link bỏ riêng tiêu chí đó (dựng URL bằng request()->except(...)). Ưu tiên thấp.

## 6. Trạng thái rỗng (xóa #bf-no-matching-flights và JS ẩn/hiện)
Dùng @forelse với 3 nhánh, xét theo thứ tự:
1. $flights rỗng VÀ $hasActiveFilters VÀ $facets['total'] > 0: "Không có chuyến nào khớp bộ lọc" + nút <a href="{{ $filterAction }}"> "Đặt lại bộ lọc".
2. $flights rỗng VÀ $legIndex > 0: giữ nguyên empty state hiện tại (chọn lại chuyến trước).
3. $flights rỗng còn lại: giữ nguyên empty state "Tìm kiếm ngày khác".

## 7. Việc phải sửa trong view
- Danh sách hãng: bỏ $flights->pluck('aircraft.airline'), bỏ nhánh fallback hãng cứng (VN, VJ, QH). Dùng $facets['airlines']; badge hiện $al['code'] (cột đúng là code, KHÔNG phải iata_code).
- Phân trang giữ nguyên: $flights->links('pagination::bootstrap-5') (server đã withQueryString nên link giữ bộ lọc).
- Ghi chú "Giá vé hiển thị đã bao gồm thuế và phí ước tính": đổi thành "Giá lọc theo giá thấp nhất của mỗi chuyến" hoặc bỏ, vì hiện không có dữ liệu thuế/phí.

## 8. Quy ước đặt tên
- Giữ tiền tố bf-. Tái sử dụng class: .bf-filter-card, .bf-filter-section, .bf-filter-option, .bf-filter-badge, .bf-time-slots, .bf-sort-group, .bf-sort-pill.
- Class mới đề xuất: .bf-price-range (nhóm 2 input giá), .bf-filter-chips (chip lọc đang bật).
- Trạng thái chọn của nút giờ và sort pill dựa vào :checked + label (CSS), không dùng class .active gán bằng JS.
- Không đổi name/value/id ở bảng mục 3. Backend validate đúng các key này.