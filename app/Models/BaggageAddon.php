<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class BaggageAddon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'weight_kg',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}