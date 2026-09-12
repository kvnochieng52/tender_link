<?php

namespace App\Services;

use App\Models\Application;
use App\Models\EvaluationCriterion;
use App\Models\Tender;
use Illuminate\Support\Collection;

/**
 * Score + rank + status engine for a tender's applications.
 *
 * Concepts:
 *   - Each criterion belongs to a section (compliance | technical | financial).
 *   - A criterion may be flagged `is_mandatory`. Mandatory criteria are
 *     scored Pass/Fail: any submitted score >= max_score/2 counts as Pass,
 *     anything below is Fail. A missing score is Pending.
 *   - If ANY mandatory criterion is Fail, the bidder is DISQUALIFIED —
 *     no total score, no rank, regardless of technical/financial scores.
 *   - Section score (%) = weighted average of non-mandatory scored criteria
 *     within the section, expressed 0–100.
 *   - Total (%) = compliance_weight * compliance% + technical_weight * technical%
 *                 + financial_weight * financial%, divided by (sum of applicable
 *                 section weights). Sections without criteria are skipped.
 *   - Rank = order of non-disqualified bidders by total desc; ties keep insertion
 *     order.
 *
 * Statuses surfaced:
 *   - "Not Configured" — the tender has no criteria in this section
 *   - "Pending"        — some criteria in this section are unscored
 *   - "Pass" / "Fail"  — for mandatory-only sections OR when the aggregate is decided
 *   - "Disqualified"   — mandatory requirement failed
 *   - "Recommended"    — top-ranked non-disqualified bidder (assigned by scoreAll)
 *   - "Eligible"       — non-disqualified fully-scored bidder (not top)
 */
class EvaluationCalculator
{
    public const CATEGORIES = ['compliance', 'technical', 'financial'];

    /**
     * Pass threshold used for MANDATORY criteria evaluated as Pass/Fail — score
     * must reach this fraction of max_score to Pass.
     */
    public const MANDATORY_PASS_FRACTION = 0.5;

    /**
     * Default section weights if the tender has none configured.
     */
    private const DEFAULT_WEIGHTS = [
        'compliance' => 0.0,
        'technical'  => 70.0,
        'financial'  => 30.0,
    ];

    /**
     * @param  Tender  $tender  With `evaluationCriteria` eager-loaded.
     * @param  Collection<int, Application>  $applications  With `evaluationScores` eager-loaded.
     * @return array<int, array<string, mixed>>  Keyed by application id.
     */
    public function scoreAll(Tender $tender, Collection $applications): array
    {
        $criteria = $tender->evaluationCriteria ?? collect();
        $byCategory = $criteria->groupBy('category');

        $weights = $this->sectionWeights($tender);

        $rows = $applications->map(function (Application $app) use ($byCategory, $weights) {
            $scoresByCriterion = $app->evaluationScores->keyBy('criterion_id');

            $mandatoryFailure = null;   // First failing mandatory criterion (short-circuits)
            $sections = [];

            foreach (self::CATEGORIES as $cat) {
                $catCriteria = $byCategory->get($cat, collect());
                $outcome = $this->sectionOutcome($catCriteria, $scoresByCriterion);
                $sections[$cat] = $outcome;

                if ($outcome['mandatory_fail']) {
                    $mandatoryFailure ??= $outcome['mandatory_fail'];
                }
            }

            $disqualified   = $mandatoryFailure !== null;
            $total          = null;
            $overallStatus  = null;

            if ($disqualified) {
                $overallStatus = 'Disqualified';
            } else {
                // Compute weighted total only if every configured section is fully scored.
                $active = array_filter($sections, fn ($s) => $s['has_criteria']);
                $fullyScored = ! empty($active)
                    && count(array_filter($active, fn ($s) => $s['score'] !== null)) === count($active);

                if ($fullyScored) {
                    $weightedSum = 0.0;
                    $weightUsed  = 0.0;
                    foreach ($active as $cat => $s) {
                        $w = (float) ($weights[$cat] ?? 0);
                        $weightedSum += $w * (float) $s['score'];
                        $weightUsed  += $w;
                    }
                    $total = $weightUsed > 0
                        ? round($weightedSum / $weightUsed, 2)
                        : (count($active) ? round(array_sum(array_column($active, 'score')) / count($active), 2) : null);
                    $overallStatus = 'Eligible';
                }
            }

            return [
                'application_id'    => $app->id,
                'compliance_score'  => $sections['compliance']['score'],
                'compliance_status' => $sections['compliance']['status'],
                'technical_score'   => $sections['technical']['score'],
                'technical_status'  => $sections['technical']['status'],
                'financial_score'   => $sections['financial']['score'],
                'financial_status'  => $sections['financial']['status'],
                'total_score'       => $total,
                'disqualified'      => $disqualified,
                'disqualification_reason' => $mandatoryFailure,
                'overall_status'    => $overallStatus, // Eligible, Disqualified, or null (pending)
            ];
        })->keyBy('application_id')->all();

        // Rank non-disqualified fully-scored bidders. Top rank gets "Recommended".
        $ranked = collect($rows)
            ->filter(fn ($r) => ! $r['disqualified'] && $r['total_score'] !== null)
            ->sortByDesc('total_score')
            ->values();

        $rank = 0;
        foreach ($ranked as $r) {
            $rank++;
            $rows[$r['application_id']]['rank'] = $rank;
            if ($rank === 1) {
                $rows[$r['application_id']]['overall_status'] = 'Recommended';
            }
        }
        // Any row that didn't get a rank stays as `null` rank.
        foreach ($rows as $id => $r) {
            $rows[$id]['rank'] = $rows[$id]['rank'] ?? null;
        }

        return $rows;
    }

    /**
     * Per-tender section weights, defaulting to 0/70/30 if unset.
     *
     * @return array{compliance: float, technical: float, financial: float}
     */
    public function sectionWeights(Tender $tender): array
    {
        $c = (float) ($tender->compliance_weight ?? 0);
        $t = (float) ($tender->technical_weight ?? 0);
        $f = (float) ($tender->financial_weight ?? 0);
        if ($c + $t + $f <= 0) {
            return self::DEFAULT_WEIGHTS;
        }
        return ['compliance' => $c, 'technical' => $t, 'financial' => $f];
    }

    /**
     * @param  Collection<int, EvaluationCriterion>  $catCriteria
     * @param  Collection<int, \App\Models\ApplicationEvaluationScore>  $scoresByCriterion
     * @return array{score: float|null, status: string, has_criteria: bool, mandatory_fail: string|null}
     */
    private function sectionOutcome(Collection $catCriteria, Collection $scoresByCriterion): array
    {
        if ($catCriteria->isEmpty()) {
            return [
                'score'          => null,
                'status'         => 'Not Configured',
                'has_criteria'   => false,
                'mandatory_fail' => null,
            ];
        }

        $mandatoryFail = null;

        // Non-mandatory criteria contribute to the weighted section score.
        $weightSum   = 0.0;
        $weightedSum = 0.0;
        $anyMissing  = false;

        foreach ($catCriteria as $c) {
            $max = (float) ($c->max_score ?: 100);
            $w   = (float) ($c->weight ?: 1);
            $s   = $scoresByCriterion->get($c->id);
            $score = $s?->score;

            if ($c->is_mandatory) {
                if ($score === null) {
                    $anyMissing = true;
                    continue;
                }
                // Pass = score >= max_score * threshold
                if ((float) $score < $max * self::MANDATORY_PASS_FRACTION) {
                    $mandatoryFail = $mandatoryFail ?: ($c->title ?: 'Mandatory requirement not met');
                }
                continue; // Mandatory criteria do NOT contribute weighted value.
            }

            // Non-mandatory scored criterion
            if ($score === null) {
                $anyMissing = true;
                continue;
            }
            $pct = $max > 0 ? ((float) $score / $max) * 100.0 : 0.0;
            $weightedSum += $pct * $w;
            $weightSum   += $w;
        }

        if ($mandatoryFail) {
            return [
                'score'          => null,
                'status'         => 'Fail',
                'has_criteria'   => true,
                'mandatory_fail' => $mandatoryFail,
            ];
        }

        if ($anyMissing) {
            return [
                'score'          => null,
                'status'         => 'Pending',
                'has_criteria'   => true,
                'mandatory_fail' => null,
            ];
        }

        // No mandatory failures and nothing pending. Compute the score if any
        // non-mandatory criteria contributed; if the section is mandatory-only
        // (Pass/Fail), we simply mark Pass with no numeric score.
        if ($weightSum <= 0) {
            return [
                'score'          => null,
                'status'         => 'Pass',
                'has_criteria'   => true,
                'mandatory_fail' => null,
            ];
        }

        $score  = round($weightedSum / $weightSum, 2);
        return [
            'score'          => $score,
            'status'         => 'Pass',
            'has_criteria'   => true,
            'mandatory_fail' => null,
        ];
    }
}
