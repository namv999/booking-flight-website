<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class SavedPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'document_number',
        'date_of_birth',
        'passenger_type_default',
        'relationship',
        'document_type',
        'nationality',
        'document_issued_country',
        'document_expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}