<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $airports = Airport::latest()->paginate(10);
        return view('admin.airports.index', compact('airports'));
    }

    public function create()
    {
        return view('admin.airports.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'iata_code' => ['required', 'string', 'max:10', 'unique:airports,iata_code'],
            'name'      => ['required', 'string', 'max:200'],
            'city'      => ['required', 'string', 'max:100'],
            'country'   => ['required', 'string', 'max:100'],
            'timezone'  => ['required', 'string', 'max:50'],
        ], [
            'iata_code.required' => 'Vui lòng nhập mã IATA sân bay.',
            'iata_code.max'      => 'Mã IATA không được vượt quá 10 ký tự. Vui lòng nhập lại!',
            'iata_code.unique'   => 'Mã IATA này đã tồn tại trong hệ thống.',
            'name.required'      => 'Vui lòng nhập tên sân bay.',
            'name.max'           => 'Tên sân bay tối đa 200 ký tự. Vui lòng nhập lại!',
            'city.required'      => 'Vui lòng nhập tên thành phố.',
            'city.max'           => 'Tên thành phố tối đa 100 ký tự.',
            'country.required'   => 'Vui lòng nhập tên quốc gia.',
            'country.max'        => 'Tên quốc gia tối đa 100 ký tự.',
            'timezone.required'  => 'Vui lòng nhập múi giờ.',
            'timezone.max'       => 'Múi giờ tối đa 50 ký tự.',
        ]);

        Airport::create($validated);

        return redirect()->route('admin.airports.index')
            ->with('success', 'Thêm sân bay thành công.');
    }

    public function update(Request $request, Airport $airport)
    {
        $validated = $request->validate([
            'iata_code' => ['required', 'string', 'max:10', 'unique:airports,iata_code,' . $airport->id],
            'name'      => ['required', 'string', 'max:200'],
            'city'      => ['required', 'string', 'max:100'],
            'country'   => ['required', 'string', 'max:100'],
            'timezone'  => ['required', 'string', 'max:50'],
        ], [
            'iata_code.required' => 'Vui lòng nhập mã IATA sân bay.',
            'iata_code.max'      => 'Mã IATA không được vượt quá 10 ký tự. Vui lòng nhập lại!',
            'iata_code.unique'   => 'Mã IATA này đã tồn tại trong hệ thống.',
            'name.required'      => 'Vui lòng nhập tên sân bay.',
            'name.max'           => 'Tên sân bay tối đa 200 ký tự. Vui lòng nhập lại!',
            'city.required'      => 'Vui lòng nhập tên thành phố.',
            'city.max'           => 'Tên thành phố tối đa 100 ký tự.',
            'country.required'   => 'Vui lòng nhập tên quốc gia.',
            'country.max'        => 'Tên quốc gia tối đa 100 ký tự.',
            'timezone.required'  => 'Vui lòng nhập múi giờ.',
            'timezone.max'       => 'Múi giờ tối đa 50 ký tự.',
        ]);

        $airport->update($validated);

        return redirect()->route('admin.airports.index')
            ->with('success', 'Cập nhật sân bay thành công.');
    }

    public function edit(Airport $airport)
    {
        return view('admin.airports.edit', compact('airport'));
    }
    public function destroy(Airport $airport)
    {
        $airport->delete();

        return redirect()->route('admin.airports.index')
            ->with('success', 'Xóa sân bay thành công.');
    }
}