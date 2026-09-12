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
        'tender_category_id',
        'application_no',
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
        'filled_questionnaire_file_path',
        'filled_questionnaire_file_name',
        'disclaimer_accepted_at',
        'due_diligence_status',
        'due_diligence_notes',
        'due_diligence_completed_at',
        'recommended_at',
        'recommendation_note',
        'bid_amount',
        'application_status_id',
        'rating',
        'evaluation_notes',
    ];

    protected $casts = [
        'rating'                     => 'decimal:2',
        'disclaimer_accepted_at'     => 'datetime',
        'due_diligence_completed_at' => 'datetime',
        'recommended_at'             => 'datetime',
        'bid_amount'                 => 'decimal:2',
    ];

    public function evaluationScores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApplicationEvaluationScore::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function applicationStatus(): BelongsTo
    {
        return $this->belongsTo(ApplicationStatus::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function tenderCategory(): BelongsTo
    {
        return $this->belongsTo(TenderCategory::class);
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
