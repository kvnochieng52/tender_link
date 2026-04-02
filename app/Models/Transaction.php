<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'trans_status_id',
        'trans_ref',
        'checkout_request_id',
        'merchant_request_id',
        'mpesa_receipt_number',
        'trans_message',
        'trans_response',
        'payment_type',
        'plan_id',
        'tender_id',
        'phone',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transactionStatus(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class, 'trans_status_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
