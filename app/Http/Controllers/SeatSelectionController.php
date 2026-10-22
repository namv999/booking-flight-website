<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\JourneyValidationService;
use Illuminate\Http\Request;

class SeatSelectionController extends Controller
{
    public function __construct(private JourneyValidationService $journey) {}

    // Client chỉ gửi flight_id + leg; hạng vé và số người lấy từ session
    public function precheck(Request $request)
    {
        $data = $request->validate([
            'flight_id' => ['required', 'integer', 'exists:flights,id'],
            'leg' => ['required', 'integer', 'min:0'],
        ]);

        $pending = session('pending_selection');
        $legIndex = (int) $data['leg'];

        if (! $pending || ! isset($pending['legs'][$legIndex])) {
            return redirect()->route('home')
                ->with('error', 'Phiên tìm kiếm đã hết hạn, vui lòng tìm chuyến bay lại.');
        }

        // Không được nhảy cóc: các chặng trước phải chọn xong
        $firstUnchosen = $this->journey->firstUnchosenLeg($pending);
        if ($firstUnchosen !== null && $legIndex > $firstUnchosen) {
            return redirect($this->journey->legUrl($pending, $firstUnchosen));
        }

        $previous = $legIndex > 0 ? Flight::find($pending['legs'][$legIndex - 1]['flight_id']) : null;

        $error = $this->journey->validateLeg($pending, $legIndex, (int) $data['flight_id'], $previous);

        if ($error) {
            return back()->with('error', $error);
        }

        // Lưu chuyến cho chặng này, xóa lựa chọn của các chặng sau (chọn lại chặng trước thì chặng sau phải chọn lại)
        $pending['legs'][$legIndex]['flight_id'] = (int) $data['flight_id'];
        for ($i = $legIndex + 1; $i < count($pending['legs']); $i++) {
            $pending['legs'][$i]['flight_id'] = null;
        }
        session(['pending_selection' => $pending]);

        $next = $this->journey->firstUnchosenLeg($pending);

        return $next === null
            ? redirect()->route('booking.passengers.form')
            : redirect()->route('booking.leg', $next);
    }
}
