<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FareClass;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'base_price'           => ['required', 'numeric', 'min:0'],
            'seat_selection_fee'   => ['required', 'numeric', 'min:0'],
            'checked_baggage_kg'   => ['required', 'integer', 'min:0'],
            'carry_on_baggage_kg'  => ['required', 'integer', 'min:0'],
            'description'          => ['nullable', 'string'],
        ], [
            'name.required' => 'Vui lòng nhập tên hạng vé.',
            'base_price.required' => 'Vui lòng nhập giá cơ bản.',
            'base_price.numeric' => 'Giá cơ bản phải là số.',
            'seat_selection_fee.required' => 'Vui lòng nhập phí chọn ghế.',
            'checked_baggage_kg.required' => 'Vui lòng nhập số kg hành lý ký gửi.',
            'carry_on_baggage_kg.required' => 'Vui lòng nhập số kg hành lý xách tay.',
        ]);

        FareClass::create($validated);

        return redirect()->route('admin.fare-classes.index')
            ->with('success', 'Thêm hạng vé thành công.');
    }

    public function edit(FareClass $fareClass)
{
    return view('admin.fare-classes.edit', compact('fareClass'));
}

    public function update(Request $request, FareClass $fareClass)
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'base_price'           => ['required', 'numeric', 'min:0'],
            'seat_selection_fee'   => ['required', 'numeric', 'min:0'],
            'checked_baggage_kg'   => ['required', 'integer', 'min:0'],
            'carry_on_baggage_kg'  => ['required', 'integer', 'min:0'],
            'description'          => ['nullable', 'string'],
        ], [
            'name.required' => 'Vui lòng nhập tên hạng vé.',
            'base_price.required' => 'Vui lòng nhập giá cơ bản.',
            'seat_selection_fee.required' => 'Vui lòng nhập phí chọn ghế.',
            'checked_baggage_kg.required' => 'Vui lòng nhập số kg hành lý ký gửi.',
            'carry_on_baggage_kg.required' => 'Vui lòng nhập số kg hành lý xách tay.',
        ]);

        $fareClass->update($validated);

        return redirect()->route('admin.fare-classes.index')
            ->with('success', 'Cập nhật hạng vé thành công.');
    }

    public function destroy(FareClass $fareClass)
    {
        $fareClass->delete();

        return redirect()->route('admin.fare-classes.index')
            ->with('success', 'Xóa hạng vé thành công.');
    }
}