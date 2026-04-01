<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'tender_requirement_id',
        'file_name',
        'filepath',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(TenderRequirement::class, 'tender_requirement_id');
    }
}
