<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreAirlineRequest;
use App\Http\Requests\Admin\UpdateAirlineRequest;

class AirlineController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $airlines = Airline::when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.airlines.index', compact('airlines', 'search'));
    }

    public function create()
    {
        return view('admin.airlines.create');
    }

    public function store(StoreAirlineRequest $request)
    {
        Airline::create($request->validated());

        return redirect()->route('admin.airlines.index')
            ->with('success', 'Thêm hãng hàng không thành công.');
    }

    public function edit(Airline $airline)
    {
        return view('admin.airlines.edit', compact('airline'));
    }

    public function update(UpdateAirlineRequest $request, Airline $airline)
    {
        $airline->update($request->validated());

        return redirect()->route('admin.airlines.index')
            ->with('success', 'Cập nhật hãng hàng không thành công.');
    }

    public function destroy(Airline $airline)
    {
        $airline->delete();

        return redirect()->route('admin.airlines.index')
            ->with('success', 'Xóa hãng hàng không thành công.');
    }
    public function show(Airline $airline)
    {
        return view('admin.airlines.show', compact('airline'));
    }
}