<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavedPassengerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SavedPassengerController extends Controller
{
    public function index(): View
    {
        $savedPassengers = auth()->user()->savedPassengers()->latest()->paginate(10);

        return view('saved-passengers.index', compact('savedPassengers'));
    }

    public function options(): JsonResponse
    {
        $items = auth()->user()->savedPassengers()
            ->orderBy('full_name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'passenger_type_default' => $p->passenger_type_default,
                'document_type' => $p->document_type,
                'document_number' => $p->document_number,
                'date_of_birth' => Carbon::make($p->date_of_birth)?->format('Y-m-d'),
                'gender' => $p->gender,
                'nationality' => $p->nationality,
                'document_issued_country' => $p->document_issued_country,
                'document_issue_date' => Carbon::make($p->document_issue_date)?->format('Y-m-d'),
                'document_expiry_date' => Carbon::make($p->document_expiry_date)?->format('Y-m-d'),
                'relationship' => $p->relationship,
            ]);

        return response()->json($items);
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