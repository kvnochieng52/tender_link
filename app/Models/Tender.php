<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'advert_file_path',
        'advert_file_name',
        'self_declaration_file_path',
        'self_declaration_file_name',
        'confidential_questionnaire_file_path',
        'confidential_questionnaire_file_name',
        'institution_id',
        'industry_id',
        'county_id',
        'closing_date_and_time',
        'expiry_date',
        'tender_status_id',
        'application_process_status_id',
        'description',
        'key_requirements',
        'tender_link_process',
        'tender_fee_amount',
        'compliance_weight',
        'technical_weight',
        'financial_weight',
        'criteria_locked_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'closing_date_and_time' => 'datetime',
        'expiry_date'           => 'datetime',
        'tender_link_process'   => 'boolean',
        'compliance_weight'     => 'decimal:2',
        'technical_weight'      => 'decimal:2',
        'financial_weight'      => 'decimal:2',
        'criteria_locked_at'    => 'datetime',
    ];

    /**
     * Restrict a query to tenders that should appear on public list pages:
     * closing deadline is still in the future AND the status is not
     * explicitly Closed/Cancelled. Tenders with no deadline set are treated
     * as still open (defensive default).
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where(function ($q) {
                $q->whereNull('closing_date_and_time')
                    ->orWhere('closing_date_and_time', '>', now());
            })
            ->whereDoesntHave('status', function ($q) {
                $q->whereIn('name', ['Closed', 'Cancelled']);
            });
    }

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

    public function categories(): HasMany
    {
        return $this->hasMany(TenderCategory::class)->orderBy('position')->orderBy('id');
    }

    public function evaluationCriteria(): HasMany
    {
        return $this->hasMany(EvaluationCriterion::class)->orderBy('category')->orderBy('position')->orderBy('id');
    }

    public function clarifications(): HasMany
    {
        return $this->hasMany(TenderClarification::class)->latest();
    }

    public function award(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TenderAward::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TenderActivityLog::class)->latest();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TenderStatus::class, 'tender_status_id');
    }

    public function applicationProcessStatus(): BelongsTo
    {
        return $this->belongsTo(ApplicationProcessStatus::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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
