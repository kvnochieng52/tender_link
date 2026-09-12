<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluationScore;
use App\Models\EvaluationCriterion;
use App\Models\Tender;
use App\Models\TenderActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TenderEvaluationController extends Controller
{
    /**
     * Bulk-save a batch of scores. Payload:
     *   scores: [{ application_id, criterion_id, score, notes }]
     * Scores are upserted per (application_id, criterion_id); a null score
     * clears the record.
     */
    public function save(Request $request, string $encryptedId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));

        $data = $request->validate([
            'scores'                  => ['required', 'array'],
            'scores.*.application_id' => ['required', 'integer', 'exists:applications,id'],
            'scores.*.criterion_id'   => ['required', 'integer', 'exists:evaluation_criteria,id'],
            'scores.*.score'          => ['nullable', 'numeric', 'min:0'],
            'scores.*.notes'          => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id;
        $updated = 0;

        DB::transaction(function () use ($tender, $data, $userId, &$updated) {
            // Whitelist criteria + applications that actually belong to this tender.
            $tenderAppIds = Application::where('tender_id', $tender->id)->pluck('id')->all();
            $tenderCriterionIds = EvaluationCriterion::where('tender_id', $tender->id)->pluck('id')->all();

            foreach ($data['scores'] as $row) {
                $appId = (int) $row['application_id'];
                $critId = (int) $row['criterion_id'];

                if (! in_array($appId, $tenderAppIds, true)) {
                    continue;
                }
                if (! in_array($critId, $tenderCriterionIds, true)) {
                    continue;
                }

                ApplicationEvaluationScore::updateOrCreate(
                    ['application_id' => $appId, 'criterion_id' => $critId],
                    [
                        'score'     => $row['score'] ?? null,
                        'notes'     => $row['notes'] ?? null,
                        'scored_by' => $userId,
                    ]
                );
                $updated++;
            }
        });

        TenderActivityLog::record(
            $tender->id,
            $userId,
            'scores.saved',
            "Saved {$updated} evaluation score(s)",
        );

        return back()->with('success', "Saved {$updated} score(s).");
    }
}
