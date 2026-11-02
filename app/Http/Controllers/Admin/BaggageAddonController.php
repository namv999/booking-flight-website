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
        $baggageAddons = BaggageAddon::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.baggage-addons.index', compact('baggageAddons'));
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
