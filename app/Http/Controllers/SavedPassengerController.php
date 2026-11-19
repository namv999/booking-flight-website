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
            ->map(function ($p) {
                $cccd = $p->cccd_number ?? ($p->document_type === 'national_id' ? $p->document_number : null);
                $cccdIssueDate = $p->cccd_issue_date ?? ($p->document_type === 'national_id' ? $p->document_issue_date : null);

                $passport = $p->passport_number ?? ($p->document_type === 'passport' ? $p->document_number : null);
                $passportIssuedCountry = $p->passport_issued_country ?? ($p->document_type === 'passport' ? $p->document_issued_country : null);
                $passportIssueDate = $p->passport_issue_date ?? ($p->document_type === 'passport' ? $p->document_issue_date : null);
                $passportExpiryDate = $p->passport_expiry_date ?? ($p->document_type === 'passport' ? $p->document_expiry_date : null);

                return [
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
                    // Trường chuyên biệt mới cho CCCD và Passport
                    'cccd_number' => $cccd,
                    'cccd_issue_date' => Carbon::make($cccdIssueDate)?->format('Y-m-d'),
                    'passport_number' => $passport,
                    'passport_issued_country' => $passportIssuedCountry,
                    'passport_issue_date' => Carbon::make($passportIssueDate)?->format('Y-m-d'),
                    'passport_expiry_date' => Carbon::make($passportExpiryDate)?->format('Y-m-d'),
                ];
            });

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
