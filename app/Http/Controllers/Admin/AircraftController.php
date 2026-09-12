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
                $query->where('model', 'like', "%{$search}%"); // Đã đổi từ model_name thành model
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

    public function store(Request $request)
{
    $validated = $request->validate([
        'airline_id'          => ['required', 'exists:airlines,id'],
        'model'               => ['required', 'string', 'max:255'],
        'registration_number' => ['required', 'string', 'max:50', 'unique:aircrafts,registration_number'],
        'total_seats'         => ['required', 'integer', 'min:1'],
    ], [
        'airline_id.required'          => 'Vui lòng chọn hãng hàng không.',
        'model.required'               => 'Vui lòng nhập model máy bay.',
        'registration_number.required' => 'Vui lòng nhập số hiệu đăng ký.',
        'registration_number.unique'   => 'Số hiệu đăng ký này đã tồn tại trong hệ thống.',
        'total_seats.required'         => 'Vui lòng nhập tổng số ghế.',
        'total_seats.integer'          => 'Tổng số ghế phải là số nguyên.',
    ]);

    Aircraft::create($validated);

    return redirect()->route('admin.aircrafts.index')
        ->with('success', 'Thêm máy bay thành công.');
}

    public function edit(Aircraft $aircraft)
{
    $airlines = \App\Models\Airline::all();
    return view('admin.aircrafts.edit', compact('aircraft', 'airlines'));
}

public function update(Request $request, Aircraft $aircraft)
{
    $validated = $request->validate([
        'airline_id'          => ['required', 'exists:airlines,id'],
        'model'               => ['required', 'string', 'max:255'],
        'registration_number' => ['required', 'string', 'max:50', 'unique:aircrafts,registration_number,' . $aircraft->id],
        'total_seats'         => ['required', 'integer', 'min:1'],
    ]);

    $aircraft->update($validated);

    return redirect()->route('admin.aircrafts.index')
        ->with('success', 'Cập nhật máy bay thành công.');
}
    public function destroy(Aircraft $aircraft)
    {
        $aircraft->delete();

        return redirect()->route('admin.aircrafts.index')
            ->with('success', 'Xóa máy bay thành công.');
    }
}
