<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'user_id',
        'company_name',
        'telephone',
        'website',
        'county_id',
        'address',
        'email',
        'representative_name',
        'representative_position',
        'representative_telephone',
        'representative_email',
        'additional_notes',
        'application_status_id',
        'rating',
        'evaluation_notes',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
    ];

    public function applicationStatus(): BelongsTo
    {
        return $this->belongsTo(ApplicationStatus::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ApplicationFile::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class)->latest();
    }
}
