<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    use HasFactory;

    protected $fillable = [
        'iata_code',
        'name',
        'city',
        'country',
        'timezone',
    ];

    public function departureFlights()
    {
        return $this->hasMany(
            Flight::class,
            'departure_airport_id'
        );
    }

    public function arrivalFlights()
    {
        return $this->hasMany(
            Flight::class,
            'arrival_airport_id'
        );
    }
}