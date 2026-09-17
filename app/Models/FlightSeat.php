<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FlightSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'flight_id',
        'seat_id',
        'fare_class_id',
        'price',
        'status',
        'held_by',
        'held_until',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'held_until' => 'datetime',
        ];
    }

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function fareClass()
    {
        return $this->belongsTo(FareClass::class);
    }

    public function heldBy()
    {
        return $this->belongsTo(
            User::class,
            'held_by'
        );
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}