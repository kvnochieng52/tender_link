<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TendererProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'trading_name',
        'registration_number',
        'kra_pin',
        'business_type',
        'year_of_registration',
        'industry_id',
        'county_id',
        'physical_address',
        'postal_address',
        'website',
        'business_description',
        'contact_person_name',
        'contact_person_position',
        'contact_phone',
        'contact_email',
        'certifications',
        'years_of_experience',
        'annual_turnover',
    ];

    protected $casts = [
        'year_of_registration' => 'integer',
        'years_of_experience'  => 'integer',
        'annual_turnover'      => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
