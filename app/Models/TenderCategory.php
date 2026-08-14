<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'tender_no',
        'title',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
