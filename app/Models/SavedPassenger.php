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
        'cccd_number',
        'cccd_issue_date',
        'passport_number',
        'passport_issued_country',
        'passport_issue_date',
        'passport_expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'document_issue_date' => 'date',
            'document_expiry_date' => 'date',
            'cccd_issue_date' => 'date',
            'passport_issue_date' => 'date',
            'passport_expiry_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SavedPassenger $p) {
            // Tương thích ngược: Đồng bộ giữa các trường chuyên biệt mới và trường cũ
            if (! empty($p->cccd_number) && empty($p->document_number)) {
                $p->document_number = $p->cccd_number;
                $p->document_type = $p->document_type ?: 'national_id';
                if (! empty($p->cccd_issue_date) && empty($p->document_issue_date)) {
                    $p->document_issue_date = $p->cccd_issue_date;
                }
            } elseif (! empty($p->passport_number) && empty($p->document_number)) {
                $p->document_number = $p->passport_number;
                $p->document_type = $p->document_type ?: 'passport';
                if (! empty($p->passport_issued_country) && empty($p->document_issued_country)) {
                    $p->document_issued_country = $p->passport_issued_country;
                }
                if (! empty($p->passport_issue_date) && empty($p->document_issue_date)) {
                    $p->document_issue_date = $p->passport_issue_date;
                }
                if (! empty($p->passport_expiry_date) && empty($p->document_expiry_date)) {
                    $p->document_expiry_date = $p->passport_expiry_date;
                }
            }

            // Nếu code cũ truyền document_number nhưng chưa điền cccd_number hoặc passport_number
            if (! empty($p->document_number)) {
                if ($p->document_type === 'passport') {
                    if (empty($p->passport_number)) {
                        $p->passport_number = $p->document_number;
                    }
                    if (empty($p->passport_issued_country)) {
                        $p->passport_issued_country = $p->document_issued_country;
                    }
                    if (empty($p->passport_issue_date)) {
                        $p->passport_issue_date = $p->document_issue_date;
                    }
                    if (empty($p->passport_expiry_date)) {
                        $p->passport_expiry_date = $p->document_expiry_date;
                    }
                } else {
                    if (empty($p->cccd_number)) {
                        $p->cccd_number = $p->document_number;
                    }
                    if (empty($p->cccd_issue_date)) {
                        $p->cccd_issue_date = $p->document_issue_date;
                    }
                }
            }
        });
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

    protected function passportIssuedCountryLabel(): Attribute
    {
        return Attribute::get(fn () => app(PassportService::class)->countryLabel($this->passport_issued_country ?? $this->document_issued_country));
    }

    protected function genderLabel(): Attribute
    {
        return Attribute::get(fn () => match ($this->gender) {
            'male' => 'Nam',
            'female' => 'Nữ',
            default => null,
        });
    }

    protected function effectiveCccdNumber(): Attribute
    {
        return Attribute::get(fn () => $this->cccd_number ?? ($this->document_type === 'national_id' ? $this->document_number : null));
    }

    protected function effectiveCccdIssueDate(): Attribute
    {
        return Attribute::get(fn () => $this->cccd_issue_date ?? ($this->document_type === 'national_id' ? $this->document_issue_date : null));
    }

    protected function effectivePassportNumber(): Attribute
    {
        return Attribute::get(fn () => $this->passport_number ?? ($this->document_type === 'passport' ? $this->document_number : null));
    }
}
