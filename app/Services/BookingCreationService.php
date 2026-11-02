<?php

namespace App\Services;

use App\Models\BaggageAddon;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingCreationService
{
    public function __construct(
        private JourneyValidationService $journey
    ) {}

    public function create(array $pending, array $passengerData, ?array $selectedSeats, User $user): Booking
    {
        $adultCount = count($passengerData['adults'] ?? []);
        $childCount = count($passengerData['children'] ?? []);
        $infantCount = count($passengerData['infants'] ?? []);

        if (
            $adultCount !== (int) $pending['adults']
            || $childCount !== (int) $pending['children']
            || $infantCount !== (int) $pending['infants']
        ) {
            throw new \InvalidArgumentException('Số lượng hành khách không khớp với lựa chọn ban đầu.');
        }

        $unchosen = $this->journey->firstUnchosenLeg($pending);
        if ($unchosen !== null) {
            throw new \InvalidArgumentException('Vui lòng chọn đủ chuyến bay cho mọi chặng.');
        }

        $flightIds = collect($pending['legs'])->pluck('flight_id');
        if ($flightIds->unique()->count() !== $flightIds->count()) {
            throw new \InvalidArgumentException('Hành trình không hợp lệ, vui lòng chọn lại chuyến bay.');
        }

        $orderedFlights = Flight::with(['departureAirport', 'arrivalAirport'])
            ->whereIn('id', $flightIds)
            ->orderBy('departure_time')
            ->get();

        if ($orderedFlights->count() !== $flightIds->count()) {
            throw new \InvalidArgumentException('Một số chuyến bay không còn tồn tại.');
        }

        $seatsNeeded = $adultCount + $childCount;
        $fareClass = FareClass::findOrFail($pending['fare_class_id']);
        $seatSelectionFee = (float) $fareClass->seat_selection_fee;

        $addonIds = collect($passengerData['adults'])->merge($passengerData['children'] ?? [])
            ->pluck('baggage_addon_id')->filter()->unique();
        $addons = BaggageAddon::whereIn('id', $addonIds)->get()->keyBy('id');
        if ($addons->count() !== $addonIds->count()) {
            throw new \InvalidArgumentException('Gói hành lý bạn chọn không còn được cung cấp. Vui lòng chọn lại.');
        }

        return DB::transaction(function () use (
            $passengerData,
            $orderedFlights,
            $seatsNeeded,
            $addons,
            $selectedSeats,
            $user,
            $fareClass,
            $seatSelectionFee
        ) {
            $seatsByFlight = [];
            $allClaimedSeatIds = [];

            foreach ($orderedFlights as $legIndex => $flight) {
                $legSelected = $selectedSeats[$legIndex] ?? [];
                $claimedInThisLeg = [];
                $passengerSeats = [];

                // 1. Validate specifically chosen seats
                foreach ($passengerData['adults'] as $pIdx => $adultRow) {
                    $chosenSeatId = $legSelected['adults'][$pIdx] ?? null;
                    if ($chosenSeatId) {
                        $seat = FlightSeat::where('id', $chosenSeatId)
                            ->where('flight_id', $flight->id)
                            ->where('fare_class_id', $fareClass->id)
                            ->where(function ($q) {
                                $q->where('status', 'available')
                                    ->orWhere(function ($q2) {
                                        $q2->where('status', 'held')
                                            ->where('held_until', '<', now());
                                    });
                            })
                            ->lockForUpdate()
                            ->first();

                        if (! $seat || in_array($seat->id, $claimedInThisLeg, true)) {
                            throw new \RuntimeException('CHOSEN_SEAT_UNAVAILABLE');
                        }

                        $claimedInThisLeg[] = $seat->id;
                        $passengerSeats['adults'][$pIdx] = [
                            'seat' => $seat,
                            'is_self_selected' => true,
                        ];
                    }
                }

                foreach ($passengerData['children'] ?? [] as $pIdx => $childRow) {
                    $chosenSeatId = $legSelected['children'][$pIdx] ?? null;
                    if ($chosenSeatId) {
                        $seat = FlightSeat::where('id', $chosenSeatId)
                            ->where('flight_id', $flight->id)
                            ->where('fare_class_id', $fareClass->id)
                            ->where(function ($q) {
                                $q->where('status', 'available')
                                    ->orWhere(function ($q2) {
                                        $q2->where('status', 'held')
                                            ->where('held_until', '<', now());
                                    });
                            })
                            ->lockForUpdate()
                            ->first();

                        if (! $seat || in_array($seat->id, $claimedInThisLeg, true)) {
                            throw new \RuntimeException('CHOSEN_SEAT_UNAVAILABLE');
                        }

                        $claimedInThisLeg[] = $seat->id;
                        $passengerSeats['children'][$pIdx] = [
                            'seat' => $seat,
                            'is_self_selected' => true,
                        ];
                    }
                }

                // 2. Auto-assign remaining seats for this leg
                $autoSeatsNeeded = $seatsNeeded - count($claimedInThisLeg);
                if ($autoSeatsNeeded > 0) {
                    $autoCandidates = FlightSeat::where('flight_id', $flight->id)
                        ->where('fare_class_id', $fareClass->id)
                        ->whereNotIn('id', $claimedInThisLeg)
                        ->where(function ($q) {
                            $q->where('status', 'available')
                                ->orWhere(function ($q2) {
                                    $q2->where('status', 'held')
                                        ->where('held_until', '<', now());
                                });
                        })
                        ->lockForUpdate()
                        ->orderBy('id')
                        ->limit($autoSeatsNeeded)
                        ->get()
                        ->sortBy('id')
                        ->values();

                    if ($autoCandidates->count() < $autoSeatsNeeded) {
                        throw new \RuntimeException('NOT_ENOUGH_SEATS');
                    }

                    $autoIndex = 0;
                    foreach ($passengerData['adults'] as $pIdx => $adultRow) {
                        if (! isset($passengerSeats['adults'][$pIdx])) {
                            $seat = $autoCandidates[$autoIndex++];
                            $claimedInThisLeg[] = $seat->id;
                            $passengerSeats['adults'][$pIdx] = [
                                'seat' => $seat,
                                'is_self_selected' => false,
                            ];
                        }
                    }

                    foreach ($passengerData['children'] ?? [] as $pIdx => $childRow) {
                        if (! isset($passengerSeats['children'][$pIdx])) {
                            $seat = $autoCandidates[$autoIndex++];
                            $claimedInThisLeg[] = $seat->id;
                            $passengerSeats['children'][$pIdx] = [
                                'seat' => $seat,
                                'is_self_selected' => false,
                            ];
                        }
                    }
                }

                $seatsByFlight[$flight->id] = $passengerSeats;
                $allClaimedSeatIds = array_merge($allClaimedSeatIds, $claimedInThisLeg);
            }

            // Mark claimed seats held
            Ticket::whereIn('flight_seat_id', $allClaimedSeatIds)->update(['flight_seat_id' => null]);
            FlightSeat::whereIn('id', $allClaimedSeatIds)->update([
                'status' => 'held',
                'held_by' => $user->id,
                'held_until' => now()->addMinutes(config('booking.seat_hold_minutes')),
            ]);

            // Create Booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => 0,
            ]);

            // Create Passengers
            $adults = [];
            $addonByPassenger = [];
            foreach (array_values($passengerData['adults']) as $row) {
                $p = $this->createPassenger($booking, $row, 'adult');
                $adults[] = $p;
                $addonId = $row['baggage_addon_id'] ?? null;
                $addonByPassenger[$p->id] = $addonId ? $addons->get($addonId) : null;
            }

            $children = [];
            foreach (array_values($passengerData['children'] ?? []) as $row) {
                $p = $this->createPassenger($booking, $row, 'child');
                $children[] = $p;
                $addonId = $row['baggage_addon_id'] ?? null;
                $addonByPassenger[$p->id] = $addonId ? $addons->get($addonId) : null;
            }

            $infants = [];
            foreach (array_values($passengerData['infants'] ?? []) as $row) {
                $infants[] = [
                    'passenger' => $this->createPassenger($booking, $row, 'infant'),
                    'companion' => $adults[(int) $row['companion_adult_index']],
                ];
            }

            // Create BookingFlights and Tickets in ascending order of flight_id (prevents deadlocks and matches schema convention)
            $totalAmount = 0;
            $flightsSortedById = $orderedFlights->sortBy('id')->values();

            foreach ($flightsSortedById as $flight) {
                $bookingFlight = BookingFlight::create([
                    'booking_id' => $booking->id,
                    'flight_id' => $flight->id,
                ]);

                $paxSeats = $seatsByFlight[$flight->id];
                $adultFarePrices = [];

                foreach ($adults as $pIdx => $adult) {
                    $seatInfo = $paxSeats['adults'][$pIdx];
                    $seat = $seatInfo['seat'];
                    $isSelfSelected = $seatInfo['is_self_selected'];
                    $addon = $addonByPassenger[$adult->id];
                    $seatFee = $isSelfSelected ? $seatSelectionFee : 0;
                    $ticketPrice = $seat->price + ($addon?->price ?? 0) + $seatFee;

                    $ticket = Ticket::create([
                        'booking_flight_id' => $bookingFlight->id,
                        'passenger_id' => $adult->id,
                        'flight_seat_id' => $seat->id,
                        'companion_adult_passenger_id' => null,
                        'is_self_selected' => $isSelfSelected,
                        'baggage_addon_id' => $addon?->id,
                        'price' => $ticketPrice,
                        'ticket_code' => strtoupper(Str::random(8)),
                    ]);

                    $adultFarePrices[$adult->id] = $seat->price;
                    $totalAmount += $ticket->price;
                }

                foreach ($children as $pIdx => $child) {
                    $seatInfo = $paxSeats['children'][$pIdx];
                    $seat = $seatInfo['seat'];
                    $isSelfSelected = $seatInfo['is_self_selected'];
                    $addon = $addonByPassenger[$child->id];
                    $seatFee = $isSelfSelected ? $seatSelectionFee : 0;
                    $ticketPrice = $seat->price + ($addon?->price ?? 0) + $seatFee;

                    $ticket = Ticket::create([
                        'booking_flight_id' => $bookingFlight->id,
                        'passenger_id' => $child->id,
                        'flight_seat_id' => $seat->id,
                        'companion_adult_passenger_id' => null,
                        'is_self_selected' => $isSelfSelected,
                        'baggage_addon_id' => $addon?->id,
                        'price' => $ticketPrice,
                        'ticket_code' => strtoupper(Str::random(8)),
                    ]);

                    $totalAmount += $ticket->price;
                }

                foreach ($infants as $infant) {
                    $infantPrice = round($adultFarePrices[$infant['companion']->id] * 0.10, 2);
                    $ticket = Ticket::create([
                        'booking_flight_id' => $bookingFlight->id,
                        'passenger_id' => $infant['passenger']->id,
                        'flight_seat_id' => null,
                        'companion_adult_passenger_id' => $infant['companion']->id,
                        'is_self_selected' => false,
                        'baggage_addon_id' => null,
                        'price' => $infantPrice,
                        'ticket_code' => strtoupper(Str::random(8)),
                    ]);

                    $totalAmount += $ticket->price;
                }
            }

            $booking->update(['total_amount' => $totalAmount]);

            return $booking;
        }, 3);
    }

    private function createPassenger(Booking $booking, array $row, string $type): Passenger
    {
        return Passenger::create([
            'booking_id' => $booking->id,
            'full_name' => $row['full_name'],
            'passenger_type' => $type,
            'document_number' => $row['document_number'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'document_type' => $row['document_type'] ?? null,
            'gender' => $row['gender'] ?? null,
            'nationality' => $row['nationality'] ?? null,
            'document_issued_country' => $row['document_issued_country'] ?? null,
            'document_issue_date' => $row['document_issue_date'] ?? null,
            'document_expiry_date' => $row['document_expiry_date'] ?? null,
        ]);
    }
}
