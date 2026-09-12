<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'application_id',
        'awarded_by',
        'awarded_at',
        'contract_value',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'awarded_at'     => 'datetime',
        'contract_value' => 'decimal:2',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}
