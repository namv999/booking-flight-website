<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFlightRequest;
use App\Http\Requests\Admin\UpdateFlightRequest;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $departureAirportId = $request->input('departure_airport_id');
        $arrivalAirportId = $request->input('arrival_airport_id');
        $airlineId = $request->input('airline_id');
        $departureDate = $request->input('departure_date');
        $sort = $request->input('sort', 'latest');

        $flightId = preg_match('/^#?(?:FL-)?0*(\d+)$/i', $search, $m) ? (int) $m[1] : null;

        $flights = Flight::with(['aircraft.airline', 'departureAirport', 'arrivalAirport'])
            ->when($search !== '', function ($query) use ($search, $flightId) {
                $query->where(function ($q) use ($search, $flightId) {
                    if ($flightId !== null) {
                        $q->orWhere('id', $flightId);
                    }
                    $q->orWhereHas('departureAirport', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('iata_code', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    })->orWhereHas('arrivalAirport', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('iata_code', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    })->orWhereHas('aircraft', function ($q2) use ($search) {
                        $q2->where('model', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%");
                    })->orWhereHas('aircraft.airline', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
                });
            })
            ->when(in_array($status, ['scheduled', 'delayed', 'cancelled'], true), fn ($q) => $q->where('status', $status))
            ->when($departureAirportId, fn ($q) => $q->where('departure_airport_id', $departureAirportId))
            ->when($arrivalAirportId, fn ($q) => $q->where('arrival_airport_id', $arrivalAirportId))
            ->when($airlineId, fn ($q) => $q->whereHas('aircraft', fn ($a) => $a->where('airline_id', $airlineId)))
            ->when($departureDate, fn ($q) => $q->whereDate('departure_time', $departureDate))
            ->when($sort === 'departure_asc', fn ($q) => $q->orderBy('departure_time', 'asc'))
            ->when($sort === 'departure_desc', fn ($q) => $q->orderBy('departure_time', 'desc'))
            ->when($sort === 'oldest', fn ($q) => $q->oldest('id'))
            ->when($sort === 'id_asc', fn ($q) => $q->orderBy('id', 'asc'))
            ->when($sort === 'id_desc', fn ($q) => $q->orderBy('id', 'desc'))
            ->when(! in_array($sort, ['departure_asc', 'departure_desc', 'oldest', 'id_asc', 'id_desc'], true), fn ($q) => $q->latest('id'))
            ->paginate(10)
            ->withQueryString();

        $airports = Airport::orderBy('iata_code')->get();
        $airlines = Airline::orderBy('name')->get();

        return view('admin.flights.index', compact(
            'flights',
            'search',
            'status',
            'departureAirportId',
            'arrivalAirportId',
            'airlineId',
            'departureDate',
            'sort',
            'airports',
            'airlines'
        ));
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
