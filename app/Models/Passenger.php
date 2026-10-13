<?php

namespace App\Models;

use App\Services\PassportService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'full_name',
        'passenger_type',
        'document_number',
        'date_of_birth',
        'document_type',
        'nationality',
        'document_issued_country',
        'document_expiry_date',
        'gender',
        'document_issue_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'document_issue_date' => 'date',
            'document_expiry_date' => 'date',
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

    // $passenger->nationality_label, ->issued_country_label, ->gender_label
    protected function nationalityLabel(): Attribute
    {
        return Attribute::get(fn () => app(PassportService::class)->countryLabel($this->nationality));
    }

    protected function issuedCountryLabel(): Attribute
    {
        return Attribute::get(fn () => app(PassportService::class)->countryLabel($this->document_issued_country));
    }

    protected function genderLabel(): Attribute
    {
        return Attribute::get(fn () => match ($this->gender) {
            'male' => 'Nam',
            'female' => 'Nữ',
            default => null,
        });
    }
}
