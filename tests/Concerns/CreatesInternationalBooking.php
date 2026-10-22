<?php

namespace Tests\Concerns;

use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Models\User;

// Dựng đơn đã đặt (đã thanh toán) với ngày bay tương đối, dùng cho test cập nhật hộ chiếu
trait CreatesInternationalBooking
{
    // $days: số ngày từ hôm nay tới ngày bay của từng chặng (âm = đã cất cánh). Chặng chẵn đi, chặng lẻ về.
    protected function bookingWithLegs(User $owner, array $days = [10], bool $international = true, array $bookingState = []): Booking
    {
        $home = Airport::factory()->create();
        $away = $international ? Airport::factory()->foreign()->create() : Airport::factory()->create();

        $booking = Booking::factory()->paid()->create(array_merge(['user_id' => $owner->id], $bookingState));

        foreach (array_values($days) as $n => $daysAhead) {
            $flight = Flight::factory()->create([
                'departure_airport_id' => $n % 2 === 0 ? $home->id : $away->id,
                'arrival_airport_id' => $n % 2 === 0 ? $away->id : $home->id,
                'departure_time' => now('UTC')->addDays($daysAhead),
                'arrival_time' => now('UTC')->addDays($daysAhead)->addHours(3),
                'status' => 'scheduled',
            ]);
            BookingFlight::factory()->create(['booking_id' => $booking->id, 'flight_id' => $flight->id]);
        }

        return $booking;
    }

    // Hành khách có hộ chiếu, kèm một vé trên MỖI chặng của đơn
    protected function addPassenger(Booking $booking, array $override = []): Passenger
    {
        $passenger = Passenger::factory()->create(array_merge([
            'booking_id' => $booking->id,
            'full_name' => 'NGUYEN VAN A',
            'passenger_type' => 'adult',
            'document_type' => 'passport',
            'document_number' => 'C1234567',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'nationality' => 'VNM',
            'document_issued_country' => 'VNM',
            'document_issue_date' => '2020-01-15',
            'document_expiry_date' => '2030-01-14',
        ], $override));

        foreach ($booking->bookingFlights()->get() as $bookingFlight) {
            Ticket::factory()->create([
                'booking_flight_id' => $bookingFlight->id,
                'passenger_id' => $passenger->id,
                'flight_seat_id' => null,
            ]);
        }

        return $passenger;
    }

    // Dữ liệu form cập nhật hợp lệ (cố tình viết thường, có khoảng trắng để kiểm tra chuẩn hóa)
    protected function validPassportPayload(array $override = []): array
    {
        return array_merge([
            'full_name' => 'nguyen van b',
            'document_number' => 'b 7654321',
            'date_of_birth' => '1991-02-03',
            'gender' => 'female',
            'nationality' => 'VNM',
            'document_issued_country' => 'THA',
            'document_issue_date' => now()->subYear()->toDateString(),
            'document_expiry_date' => now()->addYears(9)->toDateString(),
            'reason' => 'Vua duoc cap ho chieu moi',
        ], $override);
    }
}
