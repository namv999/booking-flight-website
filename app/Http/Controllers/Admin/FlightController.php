<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use App\Models\Aircraft;
use App\Models\Airport;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $flights = Flight::with(['aircraft', 'departureAirport', 'arrivalAirport'])
            ->when($search, function ($query, $search) {
                $query->whereHas('departureAirport', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                })->orWhereHas('arrivalAirport', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.flights.index', compact('flights', 'search'));
    }

    public function create()
    {
        $aircrafts = Aircraft::all();
        $airports = Airport::all();
        return view('admin.flights.create', compact('aircrafts', 'airports'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'aircraft_id'          => ['required', 'exists:aircrafts,id'],
            'departure_airport_id' => ['required', 'exists:airports,id', 'different:arrival_airport_id'],
            'arrival_airport_id'   => ['required', 'exists:airports,id'],
            'departure_time'       => ['required', 'date', 'after:now'],
            'arrival_time'         => ['required', 'date', 'after:departure_time'],
            'status'               => ['required', 'string', 'max:50'],
        ], [
            'aircraft_id.required'          => 'Vui lòng chọn máy bay.',
            'departure_airport_id.required' => 'Vui lòng chọn sân bay xuất phát.',
            'arrival_airport_id.required'   => 'Vui lòng chọn sân bay đến.',
            'departure_airport_id.different'=> 'Sân bay đi và sân bay đến phải khác nhau.',
            'departure_time.required'       => 'Vui lòng chọn thời gian đi.',
            'departure_time.after'          => 'Thời gian đi phải lớn hơn thời điểm hiện tại.',
            'arrival_time.required'         => 'Vui lòng chọn thời gian đến.',
            'arrival_time.after'            => 'Thời gian đến phải sau thời gian đi.',
            'status.required'               => 'Vui lòng chọn trạng thái chuyến bay.',
        ]);

        Flight::create($validated);

        return redirect()->route('admin.flights.index')
            ->with('success', 'Thêm chuyến bay thành công.');
    }

    public function edit(Flight $flight)
    {
        $aircrafts = Aircraft::all();
        $airports = Airport::all();
        return view('admin.flights.edit', compact('flight', 'aircrafts', 'airports'));
    }

    public function update(Request $request, Flight $flight)
    {
        $validated = $request->validate([
            'aircraft_id'          => ['required', 'exists:aircrafts,id'],
            'departure_airport_id' => ['required', 'exists:airports,id', 'different:arrival_airport_id'],
            'arrival_airport_id'   => ['required', 'exists:airports,id'],
            'departure_time'       => ['required', 'date'],
            'arrival_time'         => ['required', 'date', 'after:departure_time'],
            'status'               => ['required', 'string', 'max:50'],
        ], [
            'aircraft_id.required'          => 'Vui lòng chọn máy bay.',
            'departure_airport_id.required' => 'Vui lòng chọn sân bay xuất phát.',
            'arrival_airport_id.required'   => 'Vui lòng chọn sân bay đến.',
            'departure_airport_id.different'=> 'Sân bay đi và sân bay đến phải khác nhau.',
            'departure_time.required'       => 'Vui lòng chọn thời gian đi.',
            'arrival_time.required'         => 'Vui lòng chọn thời gian đến.',
            'arrival_time.after'            => 'Thời gian đến phải sau thời gian đi.',
            'status.required'               => 'Vui lòng chọn trạng thái chuyến bay.',
        ]);

        $flight->update($validated);

        return redirect()->route('admin.flights.index')
            ->with('success', 'Cập nhật chuyến bay thành công.');
    }

    public function destroy(Flight $flight)
    {
        $flight->delete();

        return redirect()->route('admin.flights.index')
            ->with('success', 'Xóa chuyến bay thành công.');
    }
}