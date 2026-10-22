<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportUpdateRequest extends Model
{
    // Các trường hộ chiếu nằm trong new_data / old_data (cũng là các cột của bảng passengers)
    public const FIELDS = [
        'full_name',
        'document_number',
        'date_of_birth',
        'gender',
        'nationality',
        'document_issued_country',
        'document_issue_date',
        'document_expiry_date',
    ];

    protected $fillable = [
        'booking_id',
        'passenger_id',
        'user_id',
        'new_data',
        'old_data',
        'reason',
        'status',
        'admin_id',
        'admin_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'new_data' => 'array',
            'old_data' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
