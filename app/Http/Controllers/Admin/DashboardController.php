<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Flight;
use App\Models\Airline;
use App\Models\FlightSeat;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Doanh thu theo tháng (từ payments.status = 'success')
        $revenueData = Payment::where('status', 'success')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = $revenueData[$i] ?? 0;
        }

        // 2. Số chuyến bay theo hãng hàng không
        $airlines = Airline::withCount('flights')->get();
        $airlineNames = $airlines->pluck('name');
        $airlineFlightCounts = $airlines->pluck('flights_count');

        // 3. Tỷ lệ lấp đầy ghế (booked / tổng ghế)
        $totalSeats = FlightSeat::count();
        $bookedSeats = FlightSeat::where('status', 'booked')->count();
        $occupancyRate = $totalSeats > 0 ? round(($bookedSeats / $totalSeats) * 100, 2) : 0;
        $emptyRate = 100 - $occupancyRate;

        return view('admin.dashboard', compact(
            'monthlyRevenue',
            'airlineNames',
            'airlineFlightCounts',
            'occupancyRate',
            'emptyRate',
            'totalSeats',
            'bookedSeats'
        ));
    }
}