<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderPayment extends Model
{
    protected $fillable = [
        'user_id',
        'tender_id',
        'amount_paid',
        'payment_ref',
        'status',
        'paid_at',
        'transaction_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
