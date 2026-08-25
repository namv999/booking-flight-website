<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'aircraft_id',
        'seat_number',
        'seat_class',
        'position',
    ];

    public function aircraft()
    {
        return $this->belongsTo(Aircraft::class);
    }

    public function flightSeats()
    {
        return $this->hasMany(FlightSeat::class);
    }
}