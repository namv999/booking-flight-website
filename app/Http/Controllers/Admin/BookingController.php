<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

// Admin chỉ XEM đơn đặt vé: không có create/store/edit/update/destroy
class BookingController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = in_array($request->input('status'), ['pending', 'paid', 'cancelled'], true)
            ? $request->input('status')
            : null;
        $passportStatus = $request->input('passport_status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $sort = $request->input('sort', 'latest');

        // Mã đơn hiển thị là "#BK-00012" nên nhận cả dạng "BK-00012", "#BK-00012" lẫn "12"
        $bookingId = preg_match('/^#?(?:BK-)?0*(\d+)$/i', $search, $m) ? (int) $m[1] : null;

        $bookings = Booking::with('user')
            ->withCount([
                'bookingFlights',
                'passportUpdateRequests as pending_passport_requests_count' => fn ($q) => $q->where('status', 'pending'),
                'passportUpdateRequests as total_passport_requests_count',
            ])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($passportStatus === 'pending', fn ($q) => $q->whereHas('passportUpdateRequests', fn ($p) => $p->where('status', 'pending')))
            ->when($passportStatus === 'any', fn ($q) => $q->whereHas('passportUpdateRequests'))
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->when($search !== '', function ($q) use ($search, $bookingId) {
                // orWhere nằm TRONG where(function) để không rò rỉ điều kiện ra ngoài bộ lọc status
                $q->where(function ($q2) use ($search, $bookingId) {
                    if ($bookingId !== null) {
                        $q2->orWhere('id', $bookingId);
                    }

                    $q2->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('bookingFlights.tickets', fn ($t) => $t->where('ticket_code', 'like', "%{$search}%"));
                });
            })
            ->when($sort === 'oldest', fn ($q) => $q->oldest('created_at')->oldest('id'))
            ->when($sort === 'amount_desc', fn ($q) => $q->orderBy('total_amount', 'desc')->latest('id'))
            ->when($sort === 'amount_asc', fn ($q) => $q->orderBy('total_amount', 'asc')->latest('id'))
            ->when($sort === 'id_asc', fn ($q) => $q->orderBy('id', 'asc'))
            ->when($sort === 'id_desc', fn ($q) => $q->orderBy('id', 'desc'))
            ->when(! in_array($sort, ['oldest', 'amount_desc', 'amount_asc', 'id_asc', 'id_desc'], true), fn ($q) => $q->latest()->latest('id'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'search', 'status', 'passportStatus', 'fromDate', 'toDate', 'sort'));
    }

    public function show(Booking $booking)
    {
        // withTrashed: đơn cũ vẫn tham chiếu hãng bay / máy bay / sân bay đã bị admin xóa mềm
        $booking->load([
            'user', 'payment', 'passengers',
            'passportUpdateRequests' => fn ($q) => $q->latest('id'),
            'passportUpdateRequests.admin',
            'bookingFlights.flight.departureAirport' => fn ($q) => $q->withTrashed(),
            'bookingFlights.flight.arrivalAirport' => fn ($q) => $q->withTrashed(),
            'bookingFlights.flight.aircraft' => fn ($q) => $q->withTrashed(),
            'bookingFlights.flight.aircraft.airline' => fn ($q) => $q->withTrashed(),
            'bookingFlights.tickets.passenger',
            'bookingFlights.tickets.flightSeat.seat',
            'bookingFlights.tickets.baggageAddon',
        ]);

        return view('admin.bookings.show', compact('booking'));
    }
}
