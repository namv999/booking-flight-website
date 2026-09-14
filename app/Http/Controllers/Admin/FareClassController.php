<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FareClass;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreFareClassRequest;
use App\Http\Requests\Admin\UpdateFareClassRequest;

class FareClassController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $fareClasses = FareClass::when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.fare-classes.index', compact('fareClasses', 'search'));
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