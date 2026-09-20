<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavedPassengerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SavedPassengerController extends Controller
{
    public function index(): View
    {
        $savedPassengers = auth()->user()->savedPassengers()->latest()->paginate(10);

        return view('saved-passengers.index', compact('savedPassengers'));
    }

    public function create(): View
    {
        return view('saved-passengers.create');
    }

    public function store(SavedPassengerRequest $request): RedirectResponse
    {
        auth()->user()->savedPassengers()->create($request->validated());

        return redirect()->route('saved-passengers.index')->with('success', 'Đã thêm hồ sơ hành khách.');
    }

    public function edit($saved_passenger): View
    {
        $savedPassenger = auth()->user()->savedPassengers()->findOrFail($saved_passenger);

        return view('saved-passengers.edit', compact('savedPassenger'));
    }

    public function update(SavedPassengerRequest $request, $saved_passenger): RedirectResponse
    {
        $savedPassenger = auth()->user()->savedPassengers()->findOrFail($saved_passenger);
        $savedPassenger->update($request->validated());

        return redirect()->route('saved-passengers.index')->with('success', 'Đã cập nhật hồ sơ hành khách.');
    }

    public function destroy($saved_passenger): RedirectResponse
    {
        $savedPassenger = auth()->user()->savedPassengers()->findOrFail($saved_passenger);
        $savedPassenger->delete();

        return redirect()->route('saved-passengers.index')->with('success', 'Đã xóa hồ sơ hành khách.');
    }
}