<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookingFlights()
    {
        return $this->hasMany(BookingFlight::class);
    }

    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Các chặng theo thứ tự bay (đã chốt: suy ra từ departure_time, không có cột leg_order)
    // Yêu cầu đã eager load bookingFlights.flight
    public function getOrderedLegsAttribute()
    {
        return $this->bookingFlights
            ->sortBy(fn ($bf) => [$bf->flight->getRawOriginal('departure_time'), $bf->flight_id])
            ->values();
    }
}
