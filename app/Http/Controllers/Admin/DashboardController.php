<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\PassportUpdateRequest;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Doanh thu theo tháng (từ payments.status = 'success') - Database agnostic (SQLite / MySQL)
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' ? "CAST(strftime('%m', created_at) AS INTEGER)" : 'MONTH(created_at)';

        $revenueData = Payment::where('status', 'success')
            ->select(
                DB::raw("{$monthExpr} as month"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = (float) ($revenueData[$i] ?? 0);
        }

        // 2. Số chuyến bay theo từng hãng hàng không
        $airlines = Airline::withCount('flights')->get();
        $airlineNames = $airlines->pluck('name');
        $airlineFlightCounts = $airlines->pluck('flights_count');

        // 3. Tỷ lệ lấp đầy ghế (booked / tổng ghế)
        $totalSeats = FlightSeat::count();
        $bookedSeats = FlightSeat::where('status', 'booked')->count();
        $occupancyRate = $totalSeats > 0 ? round(($bookedSeats / $totalSeats) * 100, 1) : 0;
        $emptyRate = round(100 - $occupancyRate, 1);

        // 4. Các chỉ số vận hành tổng quan
        $totalRevenue = (float) Payment::where('status', 'success')->sum('amount');
        $totalBookings = Booking::count();
        $pendingBookingsCount = Booking::where('status', 'pending')->count();
        $paidBookingsCount = Booking::where('status', 'paid')->count();
        $cancelledBookingsCount = Booking::where('status', 'cancelled')->count();

        $totalFlights = Flight::count();
        $todayFlightsCount = Flight::whereDate('departure_time', Carbon::today())->count();

        // 5. Yêu cầu cập nhật hộ chiếu cần xử lý
        $pendingPassportRequests = PassportUpdateRequest::with(['booking.user', 'passenger'])
            ->where('status', 'pending')
            ->latest('id')
            ->take(5)
            ->get();
        $pendingPassportCount = PassportUpdateRequest::where('status', 'pending')->count();

        // 6. Đơn đặt vé gần đây nhất
        $recentBookings = Booking::with(['user', 'bookingFlights.flight.departureAirport', 'bookingFlights.flight.arrivalAirport'])
            ->latest('id')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'monthlyRevenue',
            'airlineNames',
            'airlineFlightCounts',
            'occupancyRate',
            'emptyRate',
            'totalSeats',
            'bookedSeats',
            'totalRevenue',
            'totalBookings',
            'pendingBookingsCount',
            'paidBookingsCount',
            'cancelledBookingsCount',
            'totalFlights',
            'todayFlightsCount',
            'pendingPassportRequests',
            'pendingPassportCount',
            'recentBookings'
        ));
    }
}
