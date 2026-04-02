<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query()
            ->with(['user:id,name,email', 'transactionStatus:id,trans_status_name,trans_status_color_code', 'plan:id,plan_name', 'tender:id,title,tender_no'])
            ->latest();

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('trans_ref', 'like', "%{$search}%")
                    ->orWhere('mpesa_receipt_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($statusId = $request->input('status_id')) {
            $query->where('trans_status_id', $statusId);
        }

        if ($type = $request->input('type')) {
            $query->where('payment_type', $type);
        }

        $transactions = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Transactions/Index', [
            'transactions'       => $transactions,
            'transactionStatuses' => TransactionStatus::select(['id', 'trans_status_name', 'trans_status_color_code'])->get(),
            'filters'            => $request->only(['q', 'status_id', 'type']),
        ]);
    }
}
