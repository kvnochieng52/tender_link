<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\Tender;
use App\Models\TenderActivityLog;
use App\Services\EvaluationCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenderReportController extends Controller
{
    /**
     * Stream a CSV of the applications table for a tender, using the same
     * calculator that renders the workspace UI so numbers match exactly.
     * Excel opens .csv natively.
     */
    public function applicationsCsv(Request $request, string $encryptedId): StreamedResponse
    {
        $tender = Tender::with(['evaluationCriteria', 'institution:id,institution_name'])
            ->findOrFail(Crypt::decryptString($encryptedId));

        $apps = Application::where('tender_id', $tender->id)
            ->with([
                'user:id,name,email',
                'user.tendererProfile:id,user_id,registration_number,years_of_experience,annual_turnover,certifications',
                'tenderCategory:id,tender_no,title',
                'applicationStatus:id,name',
                'evaluationScores',
                'county:id,name',
            ])
            ->orderBy('id')
            ->get();

        $computed = (new EvaluationCalculator())->scoreAll($tender, $apps);

        $filename = 'tender-'
            . preg_replace('/[^A-Za-z0-9\-]/', '-', $tender->tender_no ?: $tender->id)
            . '-applications-' . now()->format('Ymd-His') . '.csv';

        return new StreamedResponse(function () use ($apps, $computed) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Rank', 'Application No', 'Company', 'Bidder', 'Category',
                'County', 'Registration No', 'Years Exp', 'Annual Turnover',
                'Bid Amount', 'Submitted',
                'Compliance', 'Technical', 'Financial', 'Total',
                'Status', 'Recommended', 'DD Status', 'Disqualification Reason',
            ]);
            foreach ($apps as $a) {
                $c = $computed[$a->id] ?? [];
                $status = $c['overall_status'] ?? ($a->applicationStatus?->name ?: 'Under Review');
                fputcsv($out, [
                    $c['rank'] ?? '',
                    $a->application_no,
                    $a->company_name,
                    $a->user?->name,
                    $a->tenderCategory
                        ? ($a->tenderCategory->tender_no . ' — ' . $a->tenderCategory->title)
                        : '',
                    $a->county?->name,
                    $a->user?->tendererProfile?->registration_number,
                    $a->user?->tendererProfile?->years_of_experience,
                    $a->user?->tendererProfile?->annual_turnover,
                    $a->bid_amount,
                    optional($a->created_at)->format('Y-m-d H:i'),
                    $c['compliance_status'] ?? '',
                    $c['technical_score'] ?? '',
                    $c['financial_score'] ?? '',
                    $c['total_score'] ?? '',
                    $status,
                    $a->recommended_at ? 'Yes' : '',
                    $a->due_diligence_status,
                    $c['disqualification_reason'] ?? '',
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Evaluation report — printable HTML the browser can save as PDF.
     * Kept as HTML to avoid adding a PDF dependency for a one-off feature.
     */
    public function evaluationReport(Request $request, string $encryptedId)
    {
        $tender = Tender::with([
            'evaluationCriteria', 'institution:id,institution_name',
            'award.application:id,application_no,company_name',
        ])->findOrFail(Crypt::decryptString($encryptedId));

        $apps = Application::where('tender_id', $tender->id)
            ->with(['evaluationScores', 'applicationStatus:id,name', 'tenderCategory:id,tender_no,title'])
            ->orderBy('id')
            ->get();

        $computed = (new EvaluationCalculator())->scoreAll($tender, $apps);
        $weights  = (new EvaluationCalculator())->sectionWeights($tender);

        // Rank rows
        $rows = $apps->map(function (Application $a) use ($computed) {
            $c = $computed[$a->id] ?? [];
            return array_merge($c, [
                'company_name' => $a->company_name,
                'application_no' => $a->application_no,
                'category' => $a->tenderCategory?->tender_no,
            ]);
        })->sortBy(function ($r) {
            if ($r['disqualified'] ?? false) return PHP_INT_MAX;
            return $r['rank'] ?? PHP_INT_MAX - 1;
        })->values();

        return view('reports.evaluation-report', [
            'tender' => $tender,
            'rows'   => $rows,
            'weights'=> $weights,
        ]);
    }

    /**
     * Pick the top-ranked eligible bidder and mark them Recommended with
     * an auto-generated rationale. Doesn't touch disqualified bidders.
     */
    public function autoRecommend(Request $request, string $encryptedId)
    {
        $tender = Tender::with('evaluationCriteria')->findOrFail(Crypt::decryptString($encryptedId));
        $apps = Application::where('tender_id', $tender->id)
            ->with(['evaluationScores'])
            ->get();

        $computed = (new EvaluationCalculator())->scoreAll($tender, $apps);

        // Find rank 1 (already excludes disqualified).
        $winner = collect($computed)->firstWhere('rank', 1);
        if (! $winner) {
            return back()->withErrors(['auto_recommend' => 'No fully-scored eligible bidder to recommend. Complete the scoring first.']);
        }

        $winnerApp = $apps->firstWhere('id', $winner['application_id']);
        if (! $winnerApp) {
            return back()->withErrors(['auto_recommend' => 'Winning application not found.']);
        }

        $rationale = sprintf(
            "%s achieved the highest overall responsive score of %s%%, having passed all mandatory requirements and achieved the highest combined technical (%s) and financial (%s) evaluation score. Recommended based on the configured evaluation criteria — final award subject to the authorized procurement decision-maker.",
            $winnerApp->company_name,
            number_format((float) $winner['total_score'], 2),
            $winner['technical_score'] !== null ? number_format((float) $winner['technical_score'], 2) : 'n/a',
            $winner['financial_score'] !== null ? number_format((float) $winner['financial_score'], 2) : 'n/a',
        );

        $winnerApp->update([
            'recommended_at'      => now(),
            'recommendation_note' => $rationale,
        ]);

        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'recommendation.auto',
            'Auto-recommended top-ranked bidder ' . ($winnerApp->application_no ?: '#' . $winnerApp->id)
                . ' ' . $winnerApp->company_name,
            Application::class,
            $winnerApp->id,
        );

        return back()->with('success', 'Top-ranked bidder recommended.');
    }
}
