<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFlightRequest;
use App\Http\Requests\Admin\UpdateFlightRequest;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $flights = Flight::with(['aircraft', 'departureAirport', 'arrivalAirport'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('departureAirport', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")->orWhere('iata_code', 'like', "%{$search}%");
                    })->orWhereHas('arrivalAirport', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")->orWhere('iata_code', 'like', "%{$search}%");
                    });
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

    public function store(StoreFlightRequest $request)
    {
        Flight::create($request->validated());

        return redirect()->route('admin.flights.index')
            ->with('success', 'Thêm chuyến bay thành công.');
    }

    public function edit(Flight $flight)
    {
        $aircrafts = Aircraft::all();
        $airports = Airport::all();

        return view('admin.flights.edit', compact('flight', 'aircrafts', 'airports'));
    }

    public function update(UpdateFlightRequest $request, Flight $flight)
    {
        $flight->update($request->validated());

        return redirect()->route('admin.flights.index')
            ->with('success', 'Cập nhật chuyến bay thành công.');
    }

    public function destroy(Flight $flight)
    {
        try {
            $flight->delete();
        } catch (QueryException $e) {
            return redirect()->route('admin.flights.index')
                ->with('error', 'Không thể xóa chuyến bay này vì đã có người đặt vé.');
        }

        return redirect()->route('admin.flights.index')
            ->with('success', 'Xóa chuyến bay thành công.');
    }

    public function show(Flight $flight)
    {
        $flight->load(['aircraft.airline', 'departureAirport', 'arrivalAirport']);

        return view('admin.flights.show', compact('flight'));
    }
}
