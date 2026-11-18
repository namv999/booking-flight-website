<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #172554; }
        h2 { margin-bottom: 4px; }
        .muted { color: #475569; }
        .leg { border: 1px solid #64748b; padding: 10px; margin: 14px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #94a3b8; padding: 6px; text-align: left; }
        th { background: #e2e8f0; }
    </style>
</head>
<body>
    @php
        $zones = \DateTimeZone::listIdentifiers();
        $localTime = function ($flight, string $field, $airport) use ($zones) {
            $raw = $flight->getRawOriginal($field);
            if (! $raw) {
                return null;
            }
            $timezone = $airport?->timezone;
            if (! $timezone || ! in_array($timezone, $zones, true)) {
                $timezone = 'UTC';
            }

            return \Carbon\Carbon::parse($raw, 'UTC')->setTimezone($timezone);
        };
    @endphp
    <h2>Vé điện tử - Booking #{{ $booking->id }}</h2>
    <p class="muted">Trạng thái: Đã thanh toán</p>
    <p>Tổng tiền: {{ number_format($booking->total_amount, 0, ',', '.') }} đ</p>

    @foreach ($booking->ordered_legs as $bookingFlight)
        @php($flight = $bookingFlight->flight)
        <div class="leg">
            <strong>Chặng {{ $loop->iteration }}:</strong>
            {{ $flight->departureAirport?->iata_code ?? '---' }} → {{ $flight->arrivalAirport?->iata_code ?? '---' }}
            <span class="muted">({{ $flight->aircraft?->airline?->name ?? 'Hãng bay' }})</span>
            <br>
            Khởi hành: {{ $localTime($flight, 'departure_time', $flight->departureAirport)?->format('H:i d/m/Y') ?? '-' }}
            &nbsp;|&nbsp; Đến nơi: {{ $localTime($flight, 'arrival_time', $flight->arrivalAirport)?->format('H:i d/m/Y') ?? '-' }}
            <table>
                <thead>
                    <tr><th>Hành khách</th><th>Loại</th><th>Ghế</th><th>Mã vé</th></tr>
                </thead>
                <tbody>
                    @foreach ($bookingFlight->tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->passenger?->full_name ?? 'Hành khách' }}</td>
                            <td>{{ $ticket->passenger?->passenger_type ?? '-' }}</td>
                            <td>{{ $ticket->flightSeat?->seat?->seat_number ?? 'Ngồi cùng người lớn' }}</td>
                            <td>{{ $ticket->ticket_code }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
