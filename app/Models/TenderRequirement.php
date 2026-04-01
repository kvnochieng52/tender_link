<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderRequirement extends Model
{
    use HasFactory;

    protected $table = 'tender_requirements';

    protected $fillable = [
        'tender_id',
        'title',
        'notes',
        'mandatory',
        'source',
        'source_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
