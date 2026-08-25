<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingFlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'flight_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}