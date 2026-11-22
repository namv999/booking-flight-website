<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFareClassRequest;
use App\Http\Requests\Admin\UpdateFareClassRequest;
use App\Models\FareClass;
use Illuminate\Http\Request;

class FareClassController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $baggage = $request->input('baggage');
        $seatFee = $request->input('seat_fee');
        $sort = $request->input('sort', 'latest');

        $fareClasses = FareClass::when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })
            ->when($baggage === 'has_baggage', fn ($q) => $q->where('checked_baggage_kg', '>', 0))
            ->when($baggage === 'no_baggage', fn ($q) => $q->where(fn ($w) => $w->whereNull('checked_baggage_kg')->orWhere('checked_baggage_kg', '<=', 0)))
            ->when($seatFee === 'free', fn ($q) => $q->where(fn ($w) => $w->whereNull('seat_selection_fee')->orWhere('seat_selection_fee', '<=', 0)))
            ->when($seatFee === 'has_fee', fn ($q) => $q->where('seat_selection_fee', '>', 0))
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('base_price', 'asc'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderBy('base_price', 'desc'))
            ->when($sort === 'name_asc', fn ($q) => $q->orderBy('name', 'asc'))
            ->when($sort === 'name_desc', fn ($q) => $q->orderBy('name', 'desc'))
            ->when($sort === 'baggage_desc', fn ($q) => $q->orderBy('checked_baggage_kg', 'desc'))
            ->when($sort === 'baggage_asc', fn ($q) => $q->orderBy('checked_baggage_kg', 'asc'))
            ->when($sort === 'oldest', fn ($q) => $q->oldest('id'))
            ->when(! in_array($sort, ['price_asc', 'price_desc', 'name_asc', 'name_desc', 'baggage_desc', 'baggage_asc', 'oldest'], true), fn ($q) => $q->latest('id'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.fare-classes.index', compact('fareClasses', 'search', 'baggage', 'seatFee', 'sort'));
    }

    public function create()
    {
        return view('admin.fare-classes.create');
    }

    public function store(StoreFareClassRequest $request)
    {
        FareClass::create($request->validated());

        return redirect()->route('admin.fare-classes.index')
            ->with('success', 'Thêm hạng vé thành công.');
    }

    public function edit(FareClass $fareClass)
    {
        return view('admin.fare-classes.edit', compact('fareClass'));
    }

    public function update(UpdateFareClassRequest $request, FareClass $fareClass)
    {
        $fareClass->update($request->validated());

        return redirect()->route('admin.fare-classes.index')
            ->with('success', 'Cập nhật hạng vé thành công.');
    }

    public function destroy(FareClass $fareClass)
    {
        $fareClass->delete();

        return redirect()->route('admin.fare-classes.index')
            ->with('success', 'Xóa hạng vé thành công.');
    }

    public function show(FareClass $fareClass)
    {
        return view('admin.fare-classes.show', compact('fareClass'));
    }
}
