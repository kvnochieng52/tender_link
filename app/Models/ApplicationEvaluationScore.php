<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEvaluationScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'criterion_id',
        'score',
        'notes',
        'scored_by',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'criterion_id');
    }

    public function scorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }
}
