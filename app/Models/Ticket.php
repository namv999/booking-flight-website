<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_flight_id',
        'passenger_id',
        'flight_seat_id',
        'companion_adult_passenger_id',
        'is_self_selected',
        'baggage_addon_id',
        'price',
        'ticket_code',
    ];

    protected function casts(): array
    {
        return [
            'is_self_selected' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function bookingFlight()
    {
        return $this->belongsTo(BookingFlight::class);
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function flightSeat()
    {
        return $this->belongsTo(FlightSeat::class);
    }

    public function companionAdultPassenger()
    {
        return $this->belongsTo(
            Passenger::class,
            'companion_adult_passenger_id'
        );
    }

    public function baggageAddon()
    {
        return $this->belongsTo(BaggageAddon::class);
    }
}