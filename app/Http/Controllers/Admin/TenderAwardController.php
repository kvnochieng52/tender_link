<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Tender;
use App\Models\TenderActivityLog;
use App\Models\TenderAward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TenderAwardController extends Controller
{
    /**
     * Record (or replace) the award for a tender. There can only be one
     * winning application per tender.
     */
    public function save(Request $request, string $encryptedId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));

        $data = $request->validate([
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'reference_no'   => ['nullable', 'string', 'max:100'],
            'awarded_at'     => ['nullable', 'date'],
            'notes'          => ['nullable', 'string'],
        ]);

        $belongs = Application::where('id', $data['application_id'])
            ->where('tender_id', $tender->id)
            ->exists();
        if (! $belongs) {
            return back()->withErrors(['application_id' => 'Application does not belong to this tender.']);
        }

        $award = TenderAward::updateOrCreate(
            ['tender_id' => $tender->id],
            [
                'application_id' => $data['application_id'],
                'awarded_by'     => $request->user()?->id,
                'awarded_at'     => $data['awarded_at'] ?? now(),
                'contract_value' => $data['contract_value'] ?? null,
                'reference_no'   => $data['reference_no'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]
        );

        $app = Application::find($data['application_id']);
        $appLabel = $app
            ? (($app->application_no ? "{$app->application_no} " : '') . $app->company_name)
            : 'unknown bidder';

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'award.recorded',
            "Awarded the tender to {$appLabel}",
            TenderAward::class,
            $award->id,
        );

        return back()->with('success', 'Award recorded.');
    }

    public function destroy(Request $request, string $encryptedId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));
        $award = TenderAward::where('tender_id', $tender->id)->first();

        if ($award) {
            $award->delete();

            TenderActivityLog::record(
                $tender->id,
                $request->user()?->id,
                'award.removed',
                'Removed the tender award',
            );
        }

        return back()->with('success', 'Award removed.');
    }
}
