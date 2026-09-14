<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreAirportRequest;
use App\Http\Requests\Admin\UpdateAirportRequest;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $airports = Airport::latest()->paginate(10);
        return view('admin.airports.index', compact('airports'));
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