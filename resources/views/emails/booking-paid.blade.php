<p>Xin chào {{ $booking->user->name }},</p>
<p>
    Booking #{{ $booking->id }} đã thanh toán thành công.
    Tổng tiền: {{ number_format($booking->total_amount, 0, ',', '.') }} đ.
</p>
<p>Vé điện tử được đính kèm dưới dạng PDF.</p>
