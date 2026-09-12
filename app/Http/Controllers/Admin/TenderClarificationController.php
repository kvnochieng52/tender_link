<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Tender;
use App\Models\TenderActivityLog;
use App\Models\TenderClarification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TenderClarificationController extends Controller
{
    public function store(Request $request, string $encryptedId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));

        $data = $request->validate([
            'question'       => ['required', 'string'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
        ]);

        // If application_id is given, it must belong to this tender.
        if (! empty($data['application_id'])) {
            $belongs = Application::where('id', $data['application_id'])
                ->where('tender_id', $tender->id)
                ->exists();
            if (! $belongs) {
                return back()->withErrors(['application_id' => 'Application does not belong to this tender.']);
            }
        }

        $clar = TenderClarification::create([
            'tender_id'      => $tender->id,
            'application_id' => $data['application_id'] ?? null,
            'asked_by'       => $request->user()?->id,
            'question'       => $data['question'],
        ]);

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'clarification.created',
            'Recorded a new clarification',
            TenderClarification::class,
            $clar->id,
        );

        return back()->with('success', 'Clarification recorded.');
    }

    public function answer(Request $request, string $encryptedId, int $clarificationId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));
        $clar = TenderClarification::where('tender_id', $tender->id)->findOrFail($clarificationId);

        $data = $request->validate([
            'answer' => ['required', 'string'],
        ]);

        $clar->update([
            'answer'      => $data['answer'],
            'answered_by' => $request->user()?->id,
            'answered_at' => now(),
        ]);

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'clarification.answered',
            'Answered a clarification',
            TenderClarification::class,
            $clar->id,
        );

        return back()->with('success', 'Clarification answered.');
    }
}
