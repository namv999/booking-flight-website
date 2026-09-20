{{-- TEMP LAYOUT: đang dùng layout tạm để test, sẽ đổi sang layouts.app khi C hoàn thành --}}
@extends('layouts.temp')

@section('title', 'Lịch sử đặt vé')

@section('content')
<div class="container py-4">
    <h3 class="mb-3">Lịch sử đặt vé</h3>

    @if($bookings->isEmpty())
        <p class="text-muted">Bạn chưa có booking nào.</p>
    @else
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Mã booking</th>
                    <th>Ngày đặt</th>
                    <th>Trạng thái</th>
                    <th>Tổng tiền</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                <tr>
                    <td>#{{ $booking->id }}</td>
                    <td>{{ $booking->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $badge = match($booking->status) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'cancelled' => 'secondary',
                                default => 'light',
                            };
                        @endphp
                        <span class="badge bg-{{ $badge }}">{{ $booking->status }}</span>
                    </td>
                    <td>{{ number_format($booking->total_amount) }} đ</td>
                    <td>
                        <a href="{{ route('booking-history.show', $booking->id) }}" class="btn btn-sm btn-primary">
                            Xem chi tiết
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $bookings->links() }}
    @endif
</div>
@endsection