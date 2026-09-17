<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Aircraft extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'aircrafts';

    protected $fillable = [
        'airline_id',
        'model',
        'registration_number',
        'total_seats',
    ];

    public function airline()
    {
        return $this->belongsTo(Airline::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function flights()
    {
        return $this->hasMany(Flight::class);
    }
}