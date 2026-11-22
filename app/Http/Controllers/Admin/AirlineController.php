<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAirlineRequest;
use App\Http\Requests\Admin\UpdateAirlineRequest;
use App\Models\Airline;
use Illuminate\Http\Request;

class AirlineController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $country = $request->input('country');
        $sort = $request->input('sort', 'latest');

        $airlines = Airline::when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        })
            ->when($country, fn ($q) => $q->where('country', $country))
            ->when($sort === 'name_asc', fn ($q) => $q->orderBy('name', 'asc'))
            ->when($sort === 'name_desc', fn ($q) => $q->orderBy('name', 'desc'))
            ->when($sort === 'code_asc', fn ($q) => $q->orderBy('code', 'asc'))
            ->when($sort === 'code_desc', fn ($q) => $q->orderBy('code', 'desc'))
            ->when($sort === 'oldest', fn ($q) => $q->oldest('id'))
            ->when(! in_array($sort, ['name_asc', 'name_desc', 'code_asc', 'code_desc', 'oldest'], true), fn ($q) => $q->latest('id'))
            ->paginate(10)
            ->withQueryString();

        $countries = Airline::select('country')->whereNotNull('country')->distinct()->orderBy('country')->pluck('country');

        return view('admin.airlines.index', compact('airlines', 'search', 'country', 'sort', 'countries'));
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
