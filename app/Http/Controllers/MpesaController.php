<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tender;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\UserPlan;
use App\Models\TenderPayment;
use App\Services\MpesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(private MpesaService $mpesa) {}

    /* ------------------------------------------------------------------ */
    /*  STK PUSH INITIATION                                                 */
    /* ------------------------------------------------------------------ */

    public function stkPush(Request $request)
    {
        $request->validate([
            'phone'        => ['required', 'string', 'min:9', 'max:15'],
            'payment_type' => ['required', 'in:plan,tender'],
            'plan_id'      => ['required_if:payment_type,plan', 'nullable', 'exists:plans,id'],
            'tender_id'    => ['required_if:payment_type,tender', 'nullable', 'exists:tenders,id'],
        ]);

        $user  = Auth::user();
        $phone = MpesaService::normalisePhone($request->phone);

        // Resolve amount & description
        if ($request->payment_type === 'plan') {
            $plan   = Plan::findOrFail($request->plan_id);
            $amount = (float) $plan->amount;
            $desc   = "Tender Plug – {$plan->plan_name} plan";
            $ref    = 'PLAN-' . $plan->id . '-U' . $user->id;
        } else {
            $tender = Tender::findOrFail($request->tender_id);
            $amount = (float) ($tender->tender_fee_amount ?? 0);
            $desc   = "Tender Fee – {$tender->tender_number}";
            $ref    = 'TFE-' . $tender->id . '-U' . $user->id;
        }

        if ($amount <= 0) {
            return response()->json(['message' => 'Payment amount is invalid.'], 422);
        }

        // Create a pending transaction record
        $pendingStatus = TransactionStatus::where('trans_status_name', 'Pending')->first();
        $transaction   = Transaction::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'trans_status_id' => $pendingStatus?->id,
            'trans_ref'      => $ref,
            'payment_type'   => $request->payment_type,
            'plan_id'        => $request->payment_type === 'plan' ? $request->plan_id : null,
            'tender_id'      => $request->payment_type === 'tender' ? $request->tender_id : null,
            'phone'          => $phone,
            'created_by'     => $user->id,
        ]);

        // Initiate STK push
        $result = $this->mpesa->stkPush($phone, $amount, $ref, $desc);

        if ($result['success']) {
            $transaction->update([
                'checkout_request_id' => $result['checkout_request_id'],
                'merchant_request_id' => $result['merchant_request_id'],
                'trans_message'       => $result['message'],
            ]);

            return response()->json([
                'message'             => $result['message'],
                'checkout_request_id' => $result['checkout_request_id'],
            ]);
        }

        // STK push failed — mark transaction failed
        $failedStatus = TransactionStatus::where('trans_status_name', 'Failed')->first();
        $transaction->update([
            'trans_status_id' => $failedStatus?->id,
            'trans_message'   => $result['message'],
        ]);

        return response()->json(['message' => $result['message']], 422);
    }

    /* ------------------------------------------------------------------ */
    /*  MPESA CALLBACK (called by Safaricom)                               */
    /* ------------------------------------------------------------------ */

    public function callback(Request $request)
    {
        try {
            $rawData = $request->getContent();
            Log::info('M-Pesa callback received', ['data' => $rawData]);

            $data = json_decode($rawData, true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
                return $this->mpesaResponse();
            }

            if (isset($data['Body']['stkCallback'])) {
                $this->processStkCallback($data['Body']['stkCallback']);
            }
        } catch (\Exception $e) {
            Log::error('M-Pesa callback error', ['error' => $e->getMessage()]);
        }

        return $this->mpesaResponse();
    }

    /* ------------------------------------------------------------------ */
    /*  PRIVATE HELPERS                                                     */
    /* ------------------------------------------------------------------ */

    private function processStkCallback(array $stkCallback): void
    {
        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $merchantRequestId = $stkCallback['MerchantRequestID'] ?? null;
        $resultCode        = $stkCallback['ResultCode'] ?? -1;
        $resultDesc        = $stkCallback['ResultDesc'] ?? '';

        if (!$checkoutRequestId) {
            Log::warning('STK callback missing CheckoutRequestID');
            return;
        }

        $transaction = Transaction::where('checkout_request_id', $checkoutRequestId)->first();
        if (!$transaction) {
            Log::error('Transaction not found', ['checkout_request_id' => $checkoutRequestId]);
            return;
        }

        DB::beginTransaction();
        try {
            if ($resultCode == 0) {
                // Extract metadata
                $meta    = [];
                $items   = $stkCallback['CallbackMetadata']['Item'] ?? [];
                foreach ($items as $item) {
                    $meta[$item['Name']] = $item['Value'] ?? null;
                }

                $paidStatus = TransactionStatus::where('trans_status_name', 'Paid')->first();
                $transaction->update([
                    'trans_status_id'     => $paidStatus?->id,
                    'merchant_request_id' => $merchantRequestId,
                    'mpesa_receipt_number' => $meta['MpesaReceiptNumber'] ?? null,
                    'trans_message'       => 'Payment completed successfully via M-Pesa',
                    'updated_by'          => $transaction->user_id,
                ]);

                // Fulfil plan or tender access
                if ($transaction->payment_type === 'plan' && $transaction->plan_id) {
                    $this->activatePlan($transaction);
                } elseif ($transaction->payment_type === 'tender' && $transaction->tender_id) {
                    $this->activateTenderAccess($transaction, $meta);
                }
            } else {
                $failedStatus = TransactionStatus::where('trans_status_name', 'Failed')->first();
                $transaction->update([
                    'trans_status_id' => $failedStatus?->id,
                    'trans_message'   => $resultDesc,
                    'updated_by'      => $transaction->user_id,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error processing STK callback', ['error' => $e->getMessage(), 'transaction_id' => $transaction->id]);
        }
    }

    private function activatePlan(Transaction $transaction): void
    {
        $plan      = Plan::find($transaction->plan_id);
        $startDate = now();
        $endDate   = now()->addDays($plan->period);

        // Deactivate any existing active plans for this user
        UserPlan::where('user_id', $transaction->user_id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        UserPlan::create([
            'user_id'        => $transaction->user_id,
            'plan_id'        => $transaction->plan_id,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'is_active'      => true,
            'transaction_id' => $transaction->id,
            'created_by'     => $transaction->user_id,
        ]);

        Log::info('User plan activated', ['user_id' => $transaction->user_id, 'plan_id' => $transaction->plan_id]);
    }

    private function activateTenderAccess(Transaction $transaction, array $meta): void
    {
        TenderPayment::updateOrCreate(
            ['user_id' => $transaction->user_id, 'tender_id' => $transaction->tender_id],
            [
                'amount_paid'    => $transaction->amount,
                'payment_ref'    => $meta['MpesaReceiptNumber'] ?? $transaction->trans_ref,
                'status'         => 'paid',
                'paid_at'        => now(),
                'transaction_id' => $transaction->id,
            ]
        );

        Log::info('Tender access granted', ['user_id' => $transaction->user_id, 'tender_id' => $transaction->tender_id]);
    }

    private function mpesaResponse()
    {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    /* ------------------------------------------------------------------ */
    /*  POLL TRANSACTION STATUS                                             */
    /* ------------------------------------------------------------------ */

    public function pollStatus(string $checkoutRequestId): JsonResponse
    {
        $transaction = Transaction::where('checkout_request_id', $checkoutRequestId)
            ->where('user_id', Auth::id())
            ->with('transactionStatus')
            ->first();

        if (!$transaction) {
            return response()->json(['status' => 'Pending', 'message' => 'Waiting for payment...']);
        }

        return response()->json([
            'status'  => $transaction->transactionStatus?->trans_status_name ?? 'Pending',
            'message' => $transaction->trans_message ?? '',
        ]);
    }
}
