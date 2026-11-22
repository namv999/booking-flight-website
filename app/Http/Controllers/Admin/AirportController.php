<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAirportRequest;
use App\Http\Requests\Admin\UpdateAirportRequest;
use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $country = $request->input('country');
        $sort = $request->input('sort', 'latest');

        $airports = Airport::when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('iata_code', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        })
            ->when($country, fn ($q) => $q->where('country', $country))
            ->when($sort === 'iata_asc', fn ($q) => $q->orderBy('iata_code', 'asc'))
            ->when($sort === 'iata_desc', fn ($q) => $q->orderBy('iata_code', 'desc'))
            ->when($sort === 'name_asc', fn ($q) => $q->orderBy('name', 'asc'))
            ->when($sort === 'name_desc', fn ($q) => $q->orderBy('name', 'desc'))
            ->when($sort === 'city_asc', fn ($q) => $q->orderBy('city', 'asc'))
            ->when($sort === 'city_desc', fn ($q) => $q->orderBy('city', 'desc'))
            ->when($sort === 'oldest', fn ($q) => $q->oldest('id'))
            ->when(! in_array($sort, ['iata_asc', 'iata_desc', 'name_asc', 'name_desc', 'city_asc', 'city_desc', 'oldest'], true), fn ($q) => $q->latest('id'))
            ->paginate(10)
            ->withQueryString();

        $countries = Airport::select('country')->whereNotNull('country')->distinct()->orderBy('country')->pluck('country');

        return view('admin.airports.index', compact('airports', 'search', 'country', 'sort', 'countries'));
    }

    public function create()
    {
        return view('admin.airports.create');
    }

    public function store(StoreAirportRequest $request)
    {
        Airport::create($request->validated());

        return redirect()->route('admin.airports.index')
            ->with('success', 'Thêm sân bay thành công.');
    }

    public function update(UpdateAirportRequest $request, Airport $airport)
    {
        $airport->update($request->validated());

        return redirect()->route('admin.airports.index')
            ->with('success', 'Cập nhật sân bay thành công.');
    }

    public function edit(Airport $airport)
    {
        return view('admin.airports.edit', compact('airport'));
    }

    public function destroy(Airport $airport)
    {
        $airport->delete();

        return redirect()->route('admin.airports.index')
            ->with('success', 'Xóa sân bay thành công.');
    }

    public function show(Airport $airport)
    {
        return view('admin.airports.show', compact('airport'));
    }
}
