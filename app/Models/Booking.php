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
}