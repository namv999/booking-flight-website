{{-- TEMP LAYOUT: đang dùng layout tạm để test, sẽ đổi sang layouts.app khi C hoàn thành --}}
@extends('layouts.temp')

@section('title', 'Chi tiết booking #' . $booking->id)

@section('content')
<div class="container py-4">
    <a href="{{ route('booking-history.index') }}" class="btn btn-outline-secondary btn-sm mb-3">&laquo; Quay lại</a>

    <h4>Booking #{{ $booking->id }}</h4>
    <p>
        Trạng thái: <strong>{{ $booking->status }}</strong> |
        Tổng tiền: <strong>{{ number_format($booking->total_amount) }} đ</strong> |
        Ngày đặt: {{ $booking->created_at->format('d/m/Y H:i') }}
    </p>

    @foreach($booking->bookingFlights as $bf)
        @php $flight = $bf->flight; @endphp
        <div class="card mb-3">
            <div class="card-header">
                {{ $flight->departureAirport->iata_code }} → {{ $flight->arrivalAirport->iata_code }}
                — {{ $flight->departure_time->format('d/m/Y H:i') }}
            </div>
            <div class="card-body">
                <p class="mb-2 text-muted">
                    Máy bay: {{ $flight->aircraft->model ?? 'N/A' }}
                    ({{ $flight->aircraft->airline->name ?? 'N/A' }})
                </p>

                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Hành khách</th>
                            <th>Loại</th>
                            <th>Ghế</th>
                            <th>Hành lý thêm</th>
                            <th>Giá vé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bf->tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->passenger->full_name }}</td>
                            <td>{{ $ticket->passenger->passenger_type }}</td>
                            <td>{{ $ticket->flightSeat->seat->seat_number ?? 'Ngồi lòng' }}</td>
                            <td>{{ $ticket->baggageAddon->name ?? '-' }}</td>
                            <td>{{ number_format($ticket->price) }} đ</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if($booking->payment)
    <div class="card">
        <div class="card-header">Thanh toán</div>
        <div class="card-body">
            Phương thức: {{ $booking->payment->method }} |
            Trạng thái: {{ $booking->payment->status }} |
            Số tiền: {{ number_format($booking->payment->amount) }} đ
        </div>
    </div>
    @endif
</div>
@endsection