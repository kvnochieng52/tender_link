<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tender_id',
        'data',
        'current_step',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'data'                  => 'array',
        'current_step'          => 'integer',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function storagePath(): string
    {
        return 'application_drafts/' . $this->id;
    }
}
