<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FareClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'base_price',
        'seat_selection_fee',
        'checked_baggage_kg',
        'carry_on_baggage_kg',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'seat_selection_fee' => 'decimal:2',
            'checked_baggage_kg' => 'integer',
            'carry_on_baggage_kg' => 'integer',
        ];
    }

    public function flightSeats()
    {
        return $this->hasMany(FlightSeat::class);
    }
}