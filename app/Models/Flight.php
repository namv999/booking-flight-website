<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Flight extends Model
{
    use HasFactory, SoftDeletes
    ;

    protected $fillable = [
        'aircraft_id',
        'departure_airport_id',
        'arrival_airport_id',
        'departure_time',
        'arrival_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime',
            'arrival_time' => 'datetime',
        ];
    }

    public function aircraft()
    {
        return $this->belongsTo(Aircraft::class);
    }

    public function departureAirport()
    {
        return $this->belongsTo(
            Airport::class,
            'departure_airport_id'
        );
    }

    public function arrivalAirport()
    {
        return $this->belongsTo(
            Airport::class,
            'arrival_airport_id'
        );
    }

    public function flightSeats()
    {
        return $this->hasMany(FlightSeat::class);
    }

    public function bookingFlights()
    {
        return $this->hasMany(BookingFlight::class);
    }
}