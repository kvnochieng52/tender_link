<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderCommunication extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'application_id',
        'category',
        'subject',
        'body',
        'recipient_email',
        'recipient_name',
        'sent_by',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Human-readable label for a category enum.
     */
    public static function categoryLabels(): array
    {
        return [
            'submission_ack'         => 'Submission Acknowledgement',
            'clarification_request'  => 'Clarification Request',
            'missing_documents'      => 'Missing Document Request',
            'shortlisting'           => 'Shortlisting Notification',
            'due_diligence'          => 'Due Diligence Request',
            'award'                  => 'Award Notification',
            'unsuccessful'           => 'Unsuccessful Bidder Notification',
            'other'                  => 'Other',
        ];
    }
}
