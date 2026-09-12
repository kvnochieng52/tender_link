<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriterion;
use App\Models\Tender;
use App\Models\TenderActivityLog;
use App\Services\EvaluationCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TenderCriteriaController extends Controller
{
    /**
     * Reject any change if the tender's criteria have been locked (which we do
     * automatically once the closing date passes). Keeps the audit clean and
     * prevents mid-flight tampering.
     */
    private function guardLocked(Tender $tender): ?RedirectResponse
    {
        if ($tender->criteria_locked_at) {
            return back()->withErrors([
                'locked' => 'Evaluation criteria are locked because the tender has closed. Reopen or amend the tender to change criteria.',
            ]);
        }
        return null;
    }

    private function rules(): array
    {
        return [
            'category'       => ['required', 'in:' . implode(',', EvaluationCalculator::CATEGORIES)],
            'title'          => ['required', 'string', 'max:255'],
            'notes'          => ['nullable', 'string'],
            'max_score'      => ['required', 'numeric', 'min:0.01'],
            'weight'         => ['required', 'numeric', 'min:0.01'],
            'is_mandatory'   => ['nullable', 'boolean'],
            'scoring_method' => ['nullable', 'in:pass_fail,rating,numeric,percentage'],
        ];
    }

    public function store(Request $request, string $encryptedId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));
        if ($block = $this->guardLocked($tender)) {
            return $block;
        }

        $data = $request->validate($this->rules());

        $position = (int) EvaluationCriterion::where('tender_id', $tender->id)
            ->where('category', $data['category'])
            ->max('position');

        $criterion = EvaluationCriterion::create([
            'tender_id'      => $tender->id,
            'category'       => $data['category'],
            'title'          => $data['title'],
            'notes'          => $data['notes'] ?? null,
            'max_score'      => $data['max_score'],
            'weight'         => $data['weight'],
            'is_mandatory'   => (bool) ($data['is_mandatory'] ?? false),
            'scoring_method' => $data['scoring_method'] ?? 'percentage',
            'position'       => $position + 1,
        ]);

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'criterion.created',
            'Added ' . ($criterion->is_mandatory ? 'MANDATORY ' : '')
                . "{$data['category']} criterion \"{$data['title']}\"",
            EvaluationCriterion::class,
            $criterion->id,
        );

        return back()->with('success', 'Criterion added.');
    }

    public function update(Request $request, string $encryptedId, int $criterionId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));
        if ($block = $this->guardLocked($tender)) {
            return $block;
        }
        $criterion = EvaluationCriterion::where('tender_id', $tender->id)->findOrFail($criterionId);

        $data = $request->validate($this->rules());

        $criterion->update([
            'category'       => $data['category'],
            'title'          => $data['title'],
            'notes'          => $data['notes'] ?? null,
            'max_score'      => $data['max_score'],
            'weight'         => $data['weight'],
            'is_mandatory'   => (bool) ($data['is_mandatory'] ?? false),
            'scoring_method' => $data['scoring_method'] ?? 'percentage',
        ]);

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'criterion.updated',
            "Updated {$data['category']} criterion \"{$data['title']}\"",
            EvaluationCriterion::class,
            $criterion->id,
        );

        return back()->with('success', 'Criterion updated.');
    }

    public function destroy(Request $request, string $encryptedId, int $criterionId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));
        if ($block = $this->guardLocked($tender)) {
            return $block;
        }
        $criterion = EvaluationCriterion::where('tender_id', $tender->id)->findOrFail($criterionId);

        $title = $criterion->title;
        $category = $criterion->category;

        $criterion->delete();

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'criterion.deleted',
            "Removed {$category} criterion \"{$title}\"",
        );

        return back()->with('success', 'Criterion removed.');
    }

    /**
     * Save the per-tender section weights (Compliance / Technical / Financial).
     * Weights must add up to 100.
     */
    public function saveWeights(Request $request, string $encryptedId): RedirectResponse
    {
        $tender = Tender::findOrFail(Crypt::decryptString($encryptedId));
        if ($block = $this->guardLocked($tender)) {
            return $block;
        }

        $data = $request->validate([
            'compliance_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'technical_weight'  => ['required', 'numeric', 'min:0', 'max:100'],
            'financial_weight'  => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $total = round((float) $data['compliance_weight'] + (float) $data['technical_weight'] + (float) $data['financial_weight'], 2);
        if (abs($total - 100.0) > 0.01) {
            return back()->withErrors([
                'financial_weight' => "Weights must add up to 100 (currently {$total}).",
            ]);
        }

        $tender->update($data);

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'weights.updated',
            sprintf('Section weights set to Compliance %s%% / Technical %s%% / Financial %s%%',
                $data['compliance_weight'], $data['technical_weight'], $data['financial_weight']),
        );

        return back()->with('success', 'Section weights saved.');
    }
}
