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
        $search = $request->input('search');

        $airports = Airport::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('iata_code', 'like', "%{$search}%");
            });
        })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.airports.index', compact('airports', 'search'));
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
