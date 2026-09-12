<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a workspace activity. Failures are swallowed — audit logging
     * must never break the primary action.
     */
    public static function record(
        int $tenderId,
        ?int $userId,
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
    ): void {
        try {
            self::create([
                'tender_id'    => $tenderId,
                'user_id'      => $userId,
                'action'       => $action,
                'description'  => $description,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
            ]);
        } catch (\Throwable $e) {
            // Silent — we never want audit failures to break the primary action.
        }
    }
}
