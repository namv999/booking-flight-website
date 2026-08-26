<?php

namespace Database\Seeders;

use App\Models\BaggageAddon;
use App\Models\Booking;
use App\Models\FlightSeat;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $baggageAddon = BaggageAddon::orderBy('id')->first();

        if (!$baggageAddon) {
            return;
        }

        $bookings = Booking::with([
            'bookingFlights.flight',
            'passengers',
        ])->orderBy('id')->get();

        foreach ($bookings as $booking) {
            foreach ($booking->bookingFlights as $bookingFlight) {
                $passengers = $booking->passengers;

                $adult = $passengers
                    ->where('passenger_type', 'adult')
                    ->first();

                $child = $passengers
                    ->where('passenger_type', 'child')
                    ->first();

                $infant = $passengers
                    ->where('passenger_type', 'infant')
                    ->first();

                /*
                 * Adult
                 */
                if ($adult) {
                    $this->createTicket(
                        bookingFlightId: $bookingFlight->id,
                        passengerId: $adult->id,
                        ticketCode: 'TKT-' . str_pad(
                            $adult->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),
                        isSelfSelected: true,
                        baggageAddonId: $baggageAddon->id,
                        flightId: $bookingFlight->flight_id
                    );
                }

                /*
                 * Child
                 */
                if ($child) {
                    $this->createTicket(
                        bookingFlightId: $bookingFlight->id,
                        passengerId: $child->id,
                        ticketCode: 'TKT-' . str_pad(
                            $child->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),
                        isSelfSelected: false,
                        baggageAddonId: $baggageAddon->id,
                        flightId: $bookingFlight->flight_id
                    );
                }

                /*
                 * Infant
                 *
                 * Infant ngồi lòng:
                 * - flight_seat_id = null
                 * - companion_adult_passenger_id = adult.id
                 */
                if ($infant && $adult) {
                    Ticket::firstOrCreate(
                        [
                            'ticket_code' => 'TKT-' . str_pad(
                                $infant->id,
                                6,
                                '0',
                                STR_PAD_LEFT
                            ),
                        ],
                        [
                            'booking_flight_id' => $bookingFlight->id,
                            'passenger_id' => $infant->id,
                            'flight_seat_id' => null,
                            'companion_adult_passenger_id' => $adult->id,
                            'is_self_selected' => false,
                            'baggage_addon_id' => null,
                            'price' => 0,
                        ]
                    );
                }
            }
        }
    }

    private function createTicket(
        int $bookingFlightId,
        int $passengerId,
        string $ticketCode,
        bool $isSelfSelected,
        ?int $baggageAddonId,
        int $flightId
    ): void {
        /*
         * Nếu Seeder chạy lại và ticket đã tồn tại,
         * không tạo ticket/chiếm thêm ghế.
         */
        if (Ticket::where('ticket_code', $ticketCode)->exists()) {
            return;
        }

        /*
         * Lấy ghế available đầu tiên của đúng chuyến bay.
         */
        $flightSeat = FlightSeat::where('flight_id', $flightId)
            ->where('status', 'available')
            ->orderBy('id')
            ->first();

        if (!$flightSeat) {
            return;
        }

        /*
         * price là snapshot của giá ghế tại thời điểm tạo ticket.
         */
        $ticket = Ticket::create([
            'booking_flight_id' => $bookingFlightId,
            'passenger_id' => $passengerId,
            'flight_seat_id' => $flightSeat->id,
            'companion_adult_passenger_id' => null,
            'is_self_selected' => $isSelfSelected,
            'baggage_addon_id' => $baggageAddonId,
            'price' => $flightSeat->price,
            'ticket_code' => $ticketCode,
        ]);

        /*
         * Ghế đã được gán cho ticket → booked.
         */
        $flightSeat->update([
            'status' => 'booked',
            'held_by' => null,
            'held_until' => null,
        ]);
    }
}