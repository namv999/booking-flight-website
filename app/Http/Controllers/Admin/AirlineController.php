<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'code'     => ['required', 'string', 'max:10', 'unique:airlines,code'],
            'country'  => ['nullable', 'string', 'max:100'],
            'logo_url' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập tên hãng hàng không.',
            'code.required' => 'Vui lòng nhập mã hãng code.',
            'code.unique'   => 'Mã hãng này đã tồn tại trong hệ thống.',
        ]);

        Airline::create($validated);

        return redirect()->route('admin.airlines.index')
            ->with('success', 'Thêm hãng hàng không thành công.');
    }

    public function edit(Airline $airline)
    {
        return view('admin.airlines.edit', compact('airline'));
    }

    public function update(Request $request, Airline $airline)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'code'     => ['required', 'string', 'max:10', 'unique:airlines,code,' . $airline->id],
            'country'  => ['nullable', 'string', 'max:100'],
            'logo_url' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập tên hãng hàng không.',
            'code.required' => 'Vui lòng nhập mã hãng code.',
            'code.unique'   => 'Mã hãng này đã tồn tại trong hệ thống.',
        ]);

        $airline->update($validated);

        return redirect()->route('admin.airlines.index')
            ->with('success', 'Cập nhật hãng hàng không thành công.');
    }

    public function destroy(Airline $airline)
    {
        $airline->delete();

        return redirect()->route('admin.airlines.index')
            ->with('success', 'Xóa hãng hàng không thành công.');
    }
}