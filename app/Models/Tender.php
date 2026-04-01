<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'tender_no',
        'institution_id',
        'industry_id',
        'county_id',
        'closing_date_and_time',
        'expiry_date',
        'tender_status_id',
        'description',
        'key_requirements',
        'tender_link_process',
        'tender_fee_amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'closing_date_and_time' => 'datetime',
        'expiry_date'           => 'datetime',
        'tender_link_process'   => 'boolean',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(TenderFile::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(\App\Models\TenderRequirement::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TenderStatus::class, 'tender_status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
