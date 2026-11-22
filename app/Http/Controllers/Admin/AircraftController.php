<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAircraftRequest;
use App\Http\Requests\Admin\UpdateAircraftRequest;
use App\Models\Aircraft;
use App\Models\Airline;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AircraftController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $airlineId = $request->input('airline_id');
        $sort = $request->input('sort', 'latest');

        $aircrafts = Aircraft::with('airline')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('model', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%")
                        ->orWhereHas('airline', fn ($a) => $a->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($airlineId, fn ($q) => $q->where('airline_id', $airlineId))
            ->when($sort === 'model_asc', fn ($q) => $q->orderBy('model', 'asc'))
            ->when($sort === 'model_desc', fn ($q) => $q->orderBy('model', 'desc'))
            ->when($sort === 'seats_desc', fn ($q) => $q->orderBy('total_seats', 'desc'))
            ->when($sort === 'seats_asc', fn ($q) => $q->orderBy('total_seats', 'asc'))
            ->when($sort === 'oldest', fn ($q) => $q->oldest('id'))
            ->when(! in_array($sort, ['model_asc', 'model_desc', 'seats_desc', 'seats_asc', 'oldest'], true), fn ($q) => $q->latest('id'))
            ->paginate(10)
            ->withQueryString();

        $airlines = Airline::orderBy('name')->get();

        return view('admin.aircrafts.index', compact('aircrafts', 'search', 'airlineId', 'sort', 'airlines'));
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
        $airlines = Airline::all();

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
        try {
            $aircraft->delete();
        } catch (QueryException $e) {
            return redirect()->route('admin.aircrafts.index')
                ->with('error', 'Không thể xóa máy bay này vì đã có chuyến bay liên quan.');
        }

        return redirect()->route('admin.aircrafts.index')
            ->with('success', 'Xóa máy bay thành công.');
    }

    public function show(Aircraft $aircraft)
    {
        $aircraft->load('airline');

        return view('admin.aircrafts.show', compact('aircraft'));
    }
}
