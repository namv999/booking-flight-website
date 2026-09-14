<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Airline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'logo_url',
        'country',
    ];
    public function flights()
    {
        return $this->hasManyThrough(Flight::class, Aircraft::class);
    }

    public function aircrafts()
    {
        return $this->hasMany(Aircraft::class);
    }
}