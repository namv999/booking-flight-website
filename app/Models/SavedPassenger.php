<?php

namespace App\Models;

use App\Services\PassportService;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // $savedPassenger->nationality_label, ->issued_country_label, ->gender_label
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