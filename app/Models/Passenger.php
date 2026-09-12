<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Passenger extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'full_name',
        'passenger_type',
        'document_number',
        'date_of_birth',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function companionTickets()
    {
        return $this->hasMany(
            Ticket::class,
            'companion_adult_passenger_id'
        );
    }
}