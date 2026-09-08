{{-- TEMP LAYOUT: chờ layout thật của C --}}
@extends('layouts.app')

@section('title', 'Thanh toán')

@section('content')
    <h1>Thanh toán - Booking #{{ $booking->id }}</h1>

    @if (session('error'))
        <p style="color:red;">{{ session('error') }}</p>
    @endif
    @if (session('status'))
        <p style="color:green;">{{ session('status') }}</p>
    @endif

    <p>Hạn thanh toán: {{ $expiresAt->format('H:i:s d/m/Y') }}
        (còn {{ now()->diffForHumans($expiresAt, true) }})</p>

    <h3>Chi tiết vé</h3>
    @foreach ($booking->bookingFlights as $bf)
        <p>
            Chuyến bay: {{ $bf->flight->departure_airport_id }} →
            {{ $bf->flight->arrival_airport_id }}
            ({{ $bf->flight->departure_time }})
        </p>
        <ul>
            @foreach ($bf->tickets as $ticket)
                <li>
                    {{ $ticket->passenger->full_name }}
                    ({{ $ticket->passenger->passenger_type }})
                    - {{ number_format($ticket->price, 0, ',', '.') }} đ
                </li>
            @endforeach
        </ul>
    @endforeach

    <p><strong>Tổng tiền: {{ number_format($booking->total_amount, 0, ',', '.') }} đ</strong></p>

    <h3>Chọn phương thức thanh toán</h3>
    <form method="POST" action="{{ route('payment.store', $booking) }}">
        @csrf

        <label>
            <input type="radio" name="method" value="the_tin_dung" checked>
            Thẻ tín dụng
        </label>
        <label>
            <input type="radio" name="method" value="vi_dien_tu">
            Ví điện tử
        </label>

        <p>Mô phỏng kết quả thanh toán (test):</p>
        <button type="submit" name="simulate_result" value="success">
            Giả lập THÀNH CÔNG
        </button>
        <button type="submit" name="simulate_result" value="failed">
            Giả lập THẤT BẠI
        </button>
    </form>
@endsection