<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'category',
        'is_mandatory',
        'scoring_method',
        'title',
        'notes',
        'max_score',
        'weight',
        'position',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'max_score'    => 'decimal:2',
        'weight'       => 'decimal:2',
        'position'     => 'integer',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ApplicationEvaluationScore::class, 'criterion_id');
    }
}
