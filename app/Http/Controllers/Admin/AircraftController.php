<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Http\Requests\Admin\StoreAircraftRequest;
use App\Http\Requests\Admin\UpdateAircraftRequest;
use Illuminate\Http\Request;

class AircraftController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $aircrafts = Aircraft::with('airline')
            ->when($search, function ($query, $search) {
                $query->where('model', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.aircrafts.index', compact('aircrafts', 'search'));
    }
    public function create()
{
    $airlines = Airline::all();
    return view('admin.aircrafts.create', compact('airlines'));
}

    public function store(StoreAircraftRequest $request)
    {
        Aircraft::create($request->validated());

        return redirect()->route('admin.aircrafts.index')
            ->with('success', 'Thêm máy bay thành công.');
    }

    public function edit(Aircraft $aircraft)
    {
        $airlines = \App\Models\Airline::all();
        return view('admin.aircrafts.edit', compact('aircraft', 'airlines'));
    }

    public function update(UpdateAircraftRequest $request, Aircraft $aircraft)
    {
        $aircraft->update($request->validated());

        return redirect()->route('admin.aircrafts.index')
            ->with('success', 'Cập nhật máy bay thành công.');
    }
    public function destroy(Aircraft $aircraft)
    {
        $aircraft->delete();

        return redirect()->route('admin.aircrafts.index')
            ->with('success', 'Xóa máy bay thành công.');
    }
    public function show(Aircraft $aircraft)
    {
        $aircraft->load('airline');
        return view('admin.aircrafts.show', compact('aircraft'));
    }
}
