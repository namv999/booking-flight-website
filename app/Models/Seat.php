<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Seat extends Model
{
    use HasFactory, SoftDeletes;

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