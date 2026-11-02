@extends('layouts.admin')

@section('title', 'Chi tiết đơn #BK-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT) . ' - Jet Charter Flights')

@section('content')
@php
    // Các chặng theo thứ tự bay (suy ra từ departure_time, không có cột leg_order)
    $legs = $booking->ordered_legs;
    $legCount = $legs->count();
    $firstFlight = $legs->first()?->flight;
    $lastFlight = $legs->last()?->flight;

    // Khứ hồi = đúng 2 chặng và chặng 2 đảo chiều chặng 1. Đa chặng có thể là open-jaw nên mỗi chặng
    // luôn hiển thị điểm đi / điểm đến riêng, không giả định các chặng nối tiếp nhau.
    $isRoundTrip = $legCount === 2 && $firstFlight && $lastFlight
        && $firstFlight->departure_airport_id === $lastFlight->arrival_airport_id
        && $firstFlight->arrival_airport_id === $lastFlight->departure_airport_id;

    // Giờ bay lưu UTC trong DB; hiển thị theo múi giờ của sân bay tương ứng (múi giờ lạ thì dùng UTC)
    $utcTime = fn ($flight, string $field) => ($raw = $flight?->getRawOriginal($field))
        ? \Carbon\Carbon::parse($raw, 'UTC')
        : null;
    $localTime = function ($utc, $airport) {
        if (! $utc) {
            return null;
        }
        $tz = $airport?->timezone;
        if (! $tz || ! in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
            $tz = 'UTC';
        }

        return $utc->copy()->setTimezone($tz);
    };

    $passengerTypeLabel = fn ($type) => match ($type) {
        'child' => 'Trẻ em',
        'infant' => 'Em bé',
        default => 'Người lớn',
    };
    $dateOrDash = fn ($date) => $date ? $date->format('d/m/Y') : '-';
@endphp

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div class="d-flex align-items-center gap-3">
            <h2 class="mb-0">Đơn đặt vé #BK-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</h2>
            @if($booking->status === 'paid')
                <span class="badge bg-success text-white">Đã thanh toán</span>
            @elseif($booking->status === 'cancelled')
                <span class="badge bg-secondary text-white">Đã hủy / Hết hạn</span>
            @else
                <span class="badge bg-warning text-dark">Chờ thanh toán</span>
            @endif
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>

    @if (session('passport_status'))
        <div class="alert alert-success">{{ session('passport_status') }}</div>
    @endif
    @if (session('passport_error'))
        <div class="alert alert-danger">{{ session('passport_error') }}</div>
    @endif

    {{-- 1. NGƯỜI ĐẶT --}}
    <div class="card mb-3">
        <div class="card-header fw-bold">Người đặt</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Họ tên:</strong> {{ $booking->user?->name ?? 'Không xác định' }}</p>
                    <p class="mb-1"><strong>Email:</strong> {{ $booking->user?->email ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Ngày đặt:</strong> {{ $booking->created_at?->format('H:i d/m/Y') }}</p>
                    <p class="mb-1"><strong>Tổng tiền:</strong> {{ number_format($booking->total_amount, 0, ',', '.') }} đ</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. CÁC CHUYẾN BAY VÀ VÉ (THEO THỨ TỰ BAY) --}}
    @foreach($legs as $i => $bf)
        @php
            $flight = $bf->flight;
            $dep = $flight?->departureAirport;
            $arr = $flight?->arrivalAirport;
            $depUtc = $utcTime($flight, 'departure_time');
            $arrUtc = $utcTime($flight, 'arrival_time');
            $depLocal = $localTime($depUtc, $dep);
            $arrLocal = $localTime($arrUtc, $arr);
            $diffMinutes = ($depUtc && $arrUtc) ? abs((int) $depUtc->diffInMinutes($arrUtc)) : 0;
            $durationText = intdiv($diffMinutes, 60).'h '.str_pad($diffMinutes % 60, 2, '0', STR_PAD_LEFT).'m';
            $legLabel = $legCount > 1
                ? ($isRoundTrip ? ($i === 0 ? 'Chuyến đi' : 'Chuyến về') : 'Chặng '.($i + 1))
                : null;
            $flightStatus = match ($flight?->status) {
                'delayed' => ['Hoãn / trễ', 'bg-warning text-dark'],
                'cancelled' => ['Đã hủy', 'bg-danger text-white'],
                default => ['Đúng lịch', 'bg-primary text-white'],
            };
        @endphp

        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold">
                    @if($legLabel) <span class="badge bg-primary me-2">{{ $legLabel }}</span> @endif
                    Chuyến bay #FL-{{ str_pad($flight?->id ?? 0, 4, '0', STR_PAD_LEFT) }}
                </span>
                <span class="badge {{ $flightStatus[1] }}">{{ $flightStatus[0] }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="h4 mb-0">{{ $dep?->iata_code ?? '-' }}</div>
                        <div class="fw-semibold">{{ $dep?->city }}</div>
                        <div class="small text-muted">{{ $dep?->name }}</div>
                        @if($depLocal)
                            <div class="mt-1"><strong>{{ $depLocal->format('H:i d/m/Y') }}</strong> <span class="small text-muted">(UTC{{ $depLocal->format('P') }})</span></div>
                        @endif
                    </div>
                    <div class="col-md-4 text-md-center">
                        <div class="small text-muted">{{ $durationText }}</div>
                        <div>{{ $flight?->aircraft?->airline?->name ?? 'Hãng hàng không' }}</div>
                        <div class="small text-muted">{{ $flight?->aircraft?->model ?? 'Máy bay' }} @if($flight?->aircraft?->registration_number)({{ $flight->aircraft->registration_number }})@endif</div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="h4 mb-0">{{ $arr?->iata_code ?? '-' }}</div>
                        <div class="fw-semibold">{{ $arr?->city }}</div>
                        <div class="small text-muted">{{ $arr?->name }}</div>
                        @if($arrLocal)
                            <div class="mt-1"><strong>{{ $arrLocal->format('H:i d/m/Y') }}</strong> <span class="small text-muted">(UTC{{ $arrLocal->format('P') }})</span></div>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Hành khách</th>
                                <th>Mã vé</th>
                                <th>Ghế</th>
                                <th>Hành lý mua thêm</th>
                                <th class="text-end">Giá vé</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bf->tickets as $ticket)
                                @php
                                    $pType = $ticket->passenger?->passenger_type ?? 'adult';
                                    $seatNumber = $ticket->flightSeat?->seat?->seat_number;
                                    $companion = $ticket->companion_adult_passenger_id
                                        ? $booking->passengers->firstWhere('id', $ticket->companion_adult_passenger_id)
                                        : null;
                                @endphp
                                <tr>
                                    <td>
                                        {{ $ticket->passenger?->full_name ?? '-' }}
                                        <span class="badge bg-light text-dark border">{{ $passengerTypeLabel($pType) }}</span>
                                        @if($pType === 'infant' && $companion)
                                            <div class="small text-muted">Đi cùng {{ $companion->full_name }}</div>
                                        @endif
                                    </td>
                                    <td class="font-monospace">{{ $ticket->ticket_code }}</td>
                                    <td>
                                        @if($seatNumber)
                                            {{ $seatNumber }}
                                        @elseif($pType === 'infant')
                                            <span class="text-muted">Ngồi cùng người lớn</span>
                                        @elseif($booking->status === 'cancelled')
                                            <span class="text-muted">Đã giải phóng ghế</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ticket->baggageAddon)
                                            {{ $ticket->baggageAddon->name }} ({{ $ticket->baggageAddon->weight_kg }}kg)
                                        @else
                                            <span class="text-muted">Không mua thêm</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($ticket->price, 0, ',', '.') }} đ</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Chưa có vé.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    {{-- 3. HÀNH KHÁCH (dùng chung cho mọi chặng) --}}
    <div class="card mb-3">
        <div class="card-header fw-bold">Hành khách ({{ $booking->passengers->count() }})</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Loại</th>
                            <th>Ngày sinh</th>
                            <th>Giấy tờ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->passengers as $passenger)
                            <tr>
                                <td>{{ $passenger->full_name }}</td>
                                <td>{{ $passengerTypeLabel($passenger->passenger_type) }}</td>
                                <td>{{ $dateOrDash($passenger->date_of_birth) }}</td>
                                <td>
                                    @if($passenger->document_type === 'passport')
                                        <div><strong>Hộ chiếu:</strong> <span class="font-monospace">{{ $passenger->document_number ?: '-' }}</span></div>
                                        <div class="small"><strong>Quốc tịch:</strong> {{ $passenger->nationality_label ?? '-' }}</div>
                                        <div class="small"><strong>Giới tính:</strong> {{ $passenger->gender_label ?? '-' }}</div>
                                        <div class="small"><strong>Nước cấp:</strong> {{ $passenger->issued_country_label ?? '-' }}</div>
                                        <div class="small"><strong>Ngày cấp:</strong> {{ $dateOrDash($passenger->document_issue_date) }}</div>
                                        <div class="small"><strong>Ngày hết hạn:</strong> {{ $dateOrDash($passenger->document_expiry_date) }}</div>
                                    @elseif($passenger->document_type === 'national_id')
                                        <div><strong>CCCD/CMND:</strong> <span class="font-monospace">{{ $passenger->document_number ?: '-' }}</span></div>
                                    @else
                                        <span class="text-muted">{{ $passenger->document_number ?: 'Chưa cung cấp' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 3b. YÊU CẦU CẬP NHẬT HỘ CHIẾU: duyệt / từ chối là thao tác ghi duy nhất của trang này --}}
    @php
        $updateRequests = $booking->passportUpdateRequests;
        $pendingRequests = $updateRequests->where('status', 'pending')->count();
        $fieldLabels = [
            'full_name' => 'Họ tên',
            'document_number' => 'Số hộ chiếu',
            'date_of_birth' => 'Ngày sinh',
            'gender' => 'Giới tính',
            'nationality' => 'Quốc tịch',
            'document_issued_country' => 'Nước cấp',
            'document_issue_date' => 'Ngày cấp',
            'document_expiry_date' => 'Ngày hết hạn',
        ];
        $fmtField = function (string $field, $value) {
            if ($value === null || $value === '') {
                return '-';
            }
            if ($value instanceof \Carbon\Carbon) {
                return $value->format('d/m/Y');
            }

            return match ($field) {
                'gender' => $value === 'male' ? 'Nam' : 'Nữ',
                'nationality', 'document_issued_country' => app(\App\Services\PassportService::class)->countryLabel($value),
                'date_of_birth', 'document_issue_date', 'document_expiry_date' => \Carbon\Carbon::parse($value)->format('d/m/Y'),
                default => $value,
            };
        };
        $requestBadge = fn ($status) => match ($status) {
            'approved' => ['bg-success text-white', 'Đã duyệt'],
            'rejected' => ['bg-danger text-white', 'Đã từ chối'],
            default => ['bg-warning text-dark', 'Đang chờ duyệt'],
        };
    @endphp
    @if ($updateRequests->isNotEmpty())
        <div class="card mb-3" id="passport-update-requests">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Yêu cầu cập nhật hộ chiếu ({{ $updateRequests->count() }})</span>
                @if ($pendingRequests > 0)
                    <span class="badge bg-warning text-dark">{{ $pendingRequests }} đang chờ duyệt</span>
                @endif
            </div>
            <div class="card-body">
                @foreach ($updateRequests as $req)
                    @php
                        $reqPassenger = $booking->passengers->firstWhere('id', $req->passenger_id);
                        [$badgeClass, $badgeText] = $requestBadge($req->status);
                        // Chờ duyệt / bị từ chối: so với dữ liệu hiện tại; đã duyệt: so với dữ liệu chụp lúc duyệt
                        $before = $req->status === 'approved' ? ($req->old_data ?? []) : ($reqPassenger ? $reqPassenger->only(array_keys($fieldLabels)) : []);
                        $formFailed = (string) old('request_id') === (string) $req->id;
                    @endphp
                    <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <strong>{{ $reqPassenger?->full_name ?? 'Hành khách đã bị xóa' }}</strong>
                                <span class="badge {{ $badgeClass }} ms-1">{{ $badgeText }}</span>
                                <div class="small text-muted">Gửi lúc {{ $req->created_at?->format('H:i d/m/Y') }}</div>
                            </div>
                            @if ($req->resolved_at)
                                <div class="small text-muted text-md-end">
                                    Xử lý lúc {{ $req->resolved_at->format('H:i d/m/Y') }}
                                    @if ($req->admin) <br>bởi {{ $req->admin->name }} @endif
                                </div>
                            @endif
                        </div>

                        <p class="mb-2"><strong>Lý do:</strong> {{ $req->reason }}</p>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-2">
                                <thead>
                                    <tr>
                                        <th>Trường</th>
                                        <th>{{ $req->status === 'approved' ? 'Trước khi duyệt' : 'Hiện tại' }}</th>
                                        <th>Dữ liệu mới</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($fieldLabels as $field => $label)
                                        @php
                                            $oldValue = $before[$field] ?? null;
                                            $newValue = $req->new_data[$field] ?? null;
                                            $oldText = $fmtField($field, $oldValue);
                                            $newText = $fmtField($field, $newValue);
                                        @endphp
                                        <tr class="{{ $oldText !== $newText ? 'table-warning' : '' }}">
                                            <td>{{ $label }}</td>
                                            <td>{{ $oldText }}</td>
                                            <td>{{ $newText }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($req->admin_note)
                            <p class="mb-2"><strong>Ghi chú của admin:</strong> {{ $req->admin_note }}</p>
                        @endif

                        @if ($req->isPending())
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <form method="POST" action="{{ route('admin.passport-update-requests.approve', $req) }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="text" name="admin_note" class="form-control form-control-sm" maxlength="500" placeholder="Ghi chú (không bắt buộc)">
                                        <button type="submit" class="btn btn-success btn-sm text-nowrap">Duyệt và cập nhật</button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <form method="POST" action="{{ route('admin.passport-update-requests.reject', $req) }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="request_id" value="{{ $req->id }}">
                                        <input type="text" name="admin_note" class="form-control form-control-sm @if ($formFailed && $errors->has('admin_note')) is-invalid @endif" maxlength="500" placeholder="Lý do từ chối (bắt buộc)" required>
                                        <button type="submit" class="btn btn-outline-danger btn-sm text-nowrap">Từ chối</button>
                                    </form>
                                    @if ($formFailed && $errors->has('admin_note'))
                                        <div class="text-danger small mt-1">{{ $errors->first('admin_note') }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 4. THANH TOÁN --}}
    <div class="card mb-4">
        <div class="card-header fw-bold">Thanh toán</div>
        <div class="card-body">
            @if($booking->payment)
                @php
                    $payment = $booking->payment;
                    [$payBadgeClass, $payBadgeText] = match ($payment->status) {
                        'success' => ['bg-success text-white', 'Thành công'],
                        'failed' => ['bg-danger text-white', 'Thất bại'],
                        default => ['bg-warning text-dark', 'Đang xử lý'],
                    };
                    $methodLabel = match ($payment->method) {
                        'the_tin_dung' => 'Thẻ tín dụng / Ghi nợ quốc tế',
                        'vi_dien_tu' => 'Ví điện tử',
                        default => $payment->method,
                    };
                @endphp
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Trạng thái:</strong> <span class="badge {{ $payBadgeClass }}">{{ $payBadgeText }}</span></p>
                        <p class="mb-1"><strong>Phương thức:</strong> {{ $methodLabel }}</p>
                        <p class="mb-1"><strong>Số tiền:</strong> {{ number_format($payment->amount, 0, ',', '.') }} đ</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Mã giao dịch:</strong> <span class="font-monospace">{{ $payment->transaction_code ?: '-' }}</span></p>
                        <p class="mb-1"><strong>Thời gian thanh toán:</strong> {{ $payment->paid_at?->format('H:i:s d/m/Y') ?? '-' }}</p>
                    </div>
                </div>
            @else
                <span class="text-muted">Chưa có giao dịch thanh toán.</span>
            @endif
        </div>
    </div>
</div>
@endsection
