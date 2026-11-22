<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaggageAddonRequest;
use App\Models\BaggageAddon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BaggageAddonController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $weightRange = $request->input('weight_range');
        $sort = $request->input('sort', 'latest');

        $baggageAddons = BaggageAddon::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($weightRange === 'under_15', fn ($q) => $q->where('weight_kg', '<', 15))
            ->when($weightRange === '15_25', fn ($q) => $q->whereBetween('weight_kg', [15, 25]))
            ->when($weightRange === 'above_25', fn ($q) => $q->where('weight_kg', '>', 25))
            ->when($sort === 'weight_asc', fn ($q) => $q->orderBy('weight_kg', 'asc'))
            ->when($sort === 'weight_desc', fn ($q) => $q->orderBy('weight_kg', 'desc'))
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('price', 'asc'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderBy('price', 'desc'))
            ->when($sort === 'name_asc', fn ($q) => $q->orderBy('name', 'asc'))
            ->when($sort === 'name_desc', fn ($q) => $q->orderBy('name', 'desc'))
            ->when($sort === 'oldest', fn ($q) => $q->oldest('id'))
            ->when(! in_array($sort, ['weight_asc', 'weight_desc', 'price_asc', 'price_desc', 'name_asc', 'name_desc', 'oldest'], true), fn ($q) => $q->latest('id'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.baggage-addons.index', compact('baggageAddons', 'search', 'weightRange', 'sort'));
    }

    public function create(): View
    {
        return view('admin.baggage-addons.create');
    }

    public function store(BaggageAddonRequest $request): RedirectResponse
    {
        BaggageAddon::create($request->validated());

        return redirect()->route('admin.baggage-addons.index')->with('success', 'Đã thêm gói hành lý.');
    }

    public function edit(BaggageAddon $baggageAddon): View
    {
        return view('admin.baggage-addons.edit', compact('baggageAddon'));
    }

    public function update(BaggageAddonRequest $request, BaggageAddon $baggageAddon): RedirectResponse
    {
        $baggageAddon->update($request->validated());

        return redirect()->route('admin.baggage-addons.index')->with('success', 'Đã cập nhật gói hành lý.');
    }

    public function destroy(BaggageAddon $baggageAddon): RedirectResponse
    {
        $baggageAddon->delete();

        return redirect()->route('admin.baggage-addons.index')->with('success', 'Đã xóa gói hành lý.');
    }
}
