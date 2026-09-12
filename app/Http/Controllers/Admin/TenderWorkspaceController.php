<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\ApplicationNote;
use App\Models\ApplicationStatus;
use App\Models\County;
use App\Models\Tender;
use App\Models\TenderCommunication;
use App\Services\CommunicationTemplates;
use App\Services\EvaluationCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class TenderWorkspaceController extends Controller
{
    public function show(Request $request, string $encryptedId): Response
    {
        $id     = Crypt::decryptString($encryptedId);
        $tender = Tender::with([
            'institution:id,institution_name,logo,email,telephone,website,address',
            'industry:id,name',
            'county:id,name',
            'status:id,name',
            'applicationProcessStatus:id,name,color',
            'files:id,tender_id,file_name,filepath',
            'requirements:id,tender_id,title,notes,mandatory',
            'categories:id,tender_id,tender_no,title,position',
            'evaluationCriteria',
            'clarifications.asker:id,name',
            'clarifications.answerer:id,name',
            'clarifications.application:id,application_no,company_name',
            'award.application:id,application_no,company_name,user_id',
            'award.awardedBy:id,name',
            'activityLogs' => fn ($q) => $q->limit(200),
            'activityLogs.user:id,name',
        ])->findOrFail($id);

        $applicationStatuses = ApplicationStatus::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        // ── Load applications with everything the workspace needs ──────
        $filters = $request->only([
            'q', 'application_status_id', 'category_id',
            'date_from', 'date_to', 'compliance_status',
            'technical_status', 'due_diligence_status',
            'min_score', 'min_rank', 'shortlisted', 'recommended',
            'county_id',
            'certification', 'min_years', 'min_turnover', 'max_price',
        ]);

        $appQuery = Application::query()
            ->where('tender_id', $tender->id)
            ->with([
                'user:id,name,email',
                'user.tendererProfile:id,user_id,certifications,years_of_experience,annual_turnover',
                'tenderCategory:id,tender_no,title',
                'applicationStatus:id,name',
                'evaluationScores',
                'files',
                'county:id,name',
            ]);

        // Text search — spans application_no / company / registration / app id / bidder id / email / phone.
        if ($q = $request->input('q')) {
            $qNum = is_numeric($q) ? (int) $q : null;

            $appQuery->where(function ($qr) use ($q, $qNum) {
                $qr->where('application_no', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('representative_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('representative_email', 'like', "%{$q}%")
                    ->orWhere('telephone', 'like', "%{$q}%")
                    ->orWhere('representative_telephone', 'like', "%{$q}%")
                    ->orWhereHas('user.tendererProfile', function ($qr2) use ($q) {
                        $qr2->where('registration_number', 'like', "%{$q}%");
                    });

                if ($qNum !== null) {
                    // Numeric-only fallback for exact Application ID / Bidder ID lookups.
                    $qr->orWhere('id', $qNum)
                        ->orWhere('user_id', $qNum);
                }
            });
        }
        // Structural filters
        if ($request->filled('application_status_id')) {
            $appQuery->where('application_status_id', $request->input('application_status_id'));
        }
        if ($request->filled('category_id')) {
            $appQuery->where('tender_category_id', $request->input('category_id'));
        }
        if ($request->filled('date_from')) {
            $appQuery->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $appQuery->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('county_id')) {
            $appQuery->where('county_id', $request->input('county_id'));
        }
        if ($request->filled('max_price')) {
            $appQuery->where('bid_amount', '<=', (float) $request->input('max_price'));
        }
        // Filters that live on the bidder's TendererProfile go through the user relation.
        if ($request->filled('certification')) {
            $needle = $request->input('certification');
            $appQuery->whereHas('user.tendererProfile', function ($q) use ($needle) {
                $q->where('certifications', 'like', "%{$needle}%");
            });
        }
        if ($request->filled('min_years')) {
            $y = (int) $request->input('min_years');
            $appQuery->whereHas('user.tendererProfile', function ($q) use ($y) {
                $q->where('years_of_experience', '>=', $y);
            });
        }
        if ($request->filled('min_turnover')) {
            $t = (float) $request->input('min_turnover');
            $appQuery->whereHas('user.tendererProfile', function ($q) use ($t) {
                $q->where('annual_turnover', '>=', $t);
            });
        }
        if ($request->filled('due_diligence_status')) {
            $appQuery->where('due_diligence_status', $request->input('due_diligence_status'));
        }
        if ($request->input('shortlisted') === '1') {
            $shortlistedId = ApplicationStatus::where('name', 'Shortlisted')->value('id');
            if ($shortlistedId) {
                $appQuery->where('application_status_id', $shortlistedId);
            }
        }
        if ($request->input('recommended') === '1') {
            $appQuery->whereNotNull('recommended_at');
        }

        $rawApps = $appQuery->latest()->get();

        // Compute weighted section scores + statuses + rank across the tender.
        $calculator = new EvaluationCalculator();
        $computed   = $calculator->scoreAll($tender, $rawApps);

        // Merge + apply post-computation filters (compliance/technical status, min_score, min_rank).
        $shortlistedStatusId = ApplicationStatus::where('name', 'Shortlisted')->value('id');
        // Auto-lock the criteria once the tender's closing date has passed.
        if (
            ! $tender->criteria_locked_at
            && $tender->closing_date_and_time
            && $tender->closing_date_and_time->isPast()
        ) {
            $tender->forceFill(['criteria_locked_at' => now()])->save();
        }

        $applications = $rawApps->map(function (Application $a) use ($computed, $shortlistedStatusId) {
            $c = $computed[$a->id] ?? [];
            return [
                'id'                => $a->id,
                'application_no'    => $a->application_no,
                'encrypted_id'      => Crypt::encryptString((string) $a->id),
                'company_name'      => $a->company_name,
                'bidder_user'       => $a->user
                    ? ['id' => $a->user->id, 'name' => $a->user->name, 'email' => $a->user->email]
                    : null,
                'category'          => $a->tenderCategory
                    ? ['id' => $a->tenderCategory->id, 'tender_no' => $a->tenderCategory->tender_no, 'title' => $a->tenderCategory->title]
                    : null,
                'county'            => $a->county
                    ? ['id' => $a->county->id, 'name' => $a->county->name]
                    : null,
                'bid_amount'        => $a->bid_amount !== null ? (float) $a->bid_amount : null,
                'certifications'    => $a->user?->tendererProfile?->certifications,
                'years_of_experience' => $a->user?->tendererProfile?->years_of_experience,
                'annual_turnover'   => $a->user?->tendererProfile?->annual_turnover !== null
                    ? (float) $a->user->tendererProfile->annual_turnover
                    : null,
                'application_date'  => $a->created_at,
                'status'            => $a->applicationStatus?->name,
                'application_status_id' => $a->application_status_id,
                'shortlisted'       => $shortlistedStatusId && $a->application_status_id === $shortlistedStatusId,
                'compliance_score'  => $c['compliance_score'] ?? null,
                'compliance_status' => $c['compliance_status'] ?? 'Not Configured',
                'technical_score'   => $c['technical_score'] ?? null,
                'technical_status'  => $c['technical_status'] ?? 'Not Configured',
                'financial_score'   => $c['financial_score'] ?? null,
                'financial_status'  => $c['financial_status'] ?? 'Not Configured',
                'total_score'       => $c['total_score'] ?? null,
                'rank'              => $c['rank'] ?? null,
                'disqualified'      => $c['disqualified'] ?? false,
                'disqualification_reason' => $c['disqualification_reason'] ?? null,
                'overall_status'    => $c['overall_status'] ?? null,
                'files_count'       => $a->files->count(),
                'due_diligence_status'       => $a->due_diligence_status,
                'due_diligence_notes'        => $a->due_diligence_notes,
                'due_diligence_completed_at' => $a->due_diligence_completed_at,
                'recommended'                => (bool) $a->recommended_at,
                'recommended_at'             => $a->recommended_at,
                'recommendation_note'        => $a->recommendation_note,
                'evaluation_scores' => $a->evaluationScores->map(fn ($s) => [
                    'criterion_id' => $s->criterion_id,
                    'score'        => $s->score !== null ? (float) $s->score : null,
                    'notes'        => $s->notes,
                ])->all(),
            ];
        });

        // Post-compute filters:
        if ($request->filled('compliance_status')) {
            $applications = $applications->filter(fn ($a) => $a['compliance_status'] === $request->input('compliance_status'));
        }
        if ($request->filled('technical_status')) {
            $applications = $applications->filter(fn ($a) => $a['technical_status'] === $request->input('technical_status'));
        }
        if ($request->filled('min_score')) {
            $min = (float) $request->input('min_score');
            $applications = $applications->filter(fn ($a) => ($a['total_score'] ?? -1) >= $min);
        }
        if ($request->filled('min_rank')) {
            $r = (int) $request->input('min_rank');
            $applications = $applications->filter(fn ($a) => $a['rank'] !== null && $a['rank'] <= $r);
        }

        $applications = $applications->values();

        // ── Submitted documents (auto-tagged with tender_no + bidder) ──
        $submittedDocuments = ApplicationFile::query()
            ->whereIn('application_id', $rawApps->pluck('id'))
            ->with(['application:id,application_no,company_name,user_id,tender_id', 'application.user:id,name', 'requirement:id,title'])
            ->latest()
            ->get()
            ->map(function ($f) use ($tender) {
                return [
                    'id'             => $f->id,
                    'file_name'      => $f->file_name,
                    'filepath'       => $f->filepath,
                    'requirement'    => $f->requirement?->title,
                    'tender_no'      => $tender->tender_no,
                    'application_no' => $f->application?->application_no,
                    'company_name'   => $f->application?->company_name,
                    'bidder_name'    => $f->application?->user?->name,
                    'uploaded_at'    => $f->created_at,
                    'application_id' => $f->application_id,
                ];
            });

        // ── Communications ─────────────────────────────────────────────
        $communications = ApplicationNote::query()
            ->whereIn('application_id', $rawApps->pluck('id'))
            ->with(['application:id,application_no,company_name', 'user:id,name'])
            ->latest()
            ->limit(200)
            ->get()
            ->map(function ($n) {
                return [
                    'id'             => $n->id,
                    'note'           => $n->note,
                    'created_at'     => $n->created_at,
                    'author'         => $n->user?->name,
                    'company_name'   => $n->application?->company_name,
                    'application_no' => $n->application?->application_no,
                ];
            });

        // ── Clarifications ─────────────────────────────────────────────
        $clarifications = $tender->clarifications->map(function ($c) {
            return [
                'id'             => $c->id,
                'question'       => $c->question,
                'answer'         => $c->answer,
                'asked_by'       => $c->asker?->name,
                'answered_by'    => $c->answerer?->name,
                'answered_at'    => $c->answered_at,
                'created_at'     => $c->created_at,
                'application_id' => $c->application_id,
                'application_no' => $c->application?->application_no,
                'company_name'   => $c->application?->company_name,
            ];
        });

        // ── Award ──────────────────────────────────────────────────────
        $award = $tender->award ? [
            'id'             => $tender->award->id,
            'application_id' => $tender->award->application_id,
            'application_no' => $tender->award->application?->application_no,
            'company_name'   => $tender->award->application?->company_name,
            'contract_value' => $tender->award->contract_value !== null ? (float) $tender->award->contract_value : null,
            'reference_no'   => $tender->award->reference_no,
            'awarded_at'     => $tender->award->awarded_at,
            'awarded_by'     => $tender->award->awardedBy?->name,
            'notes'          => $tender->award->notes,
        ] : null;

        // ── Audit trail ────────────────────────────────────────────────
        $audit = $tender->activityLogs->map(function ($l) {
            return [
                'id'          => $l->id,
                'action'      => $l->action,
                'description' => $l->description,
                'author'      => $l->user?->name,
                'created_at'  => $l->created_at,
            ];
        });

        // ── Counters (all live now) ────────────────────────────────────
        $counters = $this->buildCounters($applications);

        // Counties present on any current application — for the Location filter.
        $counties = County::query()
            ->whereIn('id', $rawApps->pluck('county_id')->filter()->unique())
            ->orderBy('name')
            ->get(['id', 'name']);

        // Sent communications (workspace-scoped) — latest 200 for display.
        $sentCommunications = TenderCommunication::query()
            ->where('tender_id', $tender->id)
            ->with(['sender:id,name', 'application:id,application_no,company_name'])
            ->latest()
            ->limit(200)
            ->get()
            ->map(function ($c) {
                return [
                    'id'              => $c->id,
                    'category'        => $c->category,
                    'category_label'  => TenderCommunication::categoryLabels()[$c->category] ?? $c->category,
                    'subject'         => $c->subject,
                    'body'            => $c->body,
                    'recipient_email' => $c->recipient_email,
                    'recipient_name'  => $c->recipient_name,
                    'sent_at'         => $c->sent_at,
                    'sent_by'         => $c->sender?->name,
                    'application_no'  => $c->application?->application_no,
                    'company_name'    => $c->application?->company_name,
                    'error'           => $c->error,
                ];
            });

        // Recipient group counts — shown next to each recipient option.
        $shortlistedGroupCount = $applications->where('shortlisted', true)->count();
        $recommendedGroupCount = $applications->where('recommended', true)->count();
        $awardedGroupCount     = $award ? 1 : 0;

        // Preset communication templates (subject + body per category).
        $communicationTemplates = collect(CommunicationTemplates::all())
            ->map(function ($tpl, $key) {
                return [
                    'key'     => $key,
                    'label'   => $tpl['label'],
                    'subject' => $tpl['subject'],
                    'body'    => $tpl['body'],
                ];
            })
            ->values();

        return Inertia::render('Admin/Tenders/Workspace', [
            'encryptedId'            => $encryptedId,
            'tender'                 => $tender,
            'applicationStatuses'    => $applicationStatuses,
            'categories'             => $tender->categories,
            'counties'               => $counties,
            'evaluationCriteria'     => $tender->evaluationCriteria,
            'applications'           => $applications,
            'submittedDocuments'     => $submittedDocuments,
            'communications'         => $communications,
            'sentCommunications'     => $sentCommunications,
            'communicationTemplates' => $communicationTemplates,
            'recipientGroupCounts'   => [
                'all'         => $applications->count(),
                'shortlisted' => $shortlistedGroupCount,
                'recommended' => $recommendedGroupCount,
                'awarded'     => $awardedGroupCount,
            ],
            'clarifications'         => $clarifications,
            'award'                  => $award,
            'audit'                  => $audit,
            'counters'               => $counters,
            'filters'                => $filters,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection  $apps  Ranked + scored apps.
     * @return array<string,int>
     */
    private function buildCounters($apps): array
    {
        return [
            'applications'  => $apps->count(),
            'submitted'     => $apps->count(),
            'under_review'  => $apps->filter(
                fn ($a) => in_array($a['compliance_status'], ['Pending', 'Not Configured'], true)
                    || in_array($a['technical_status'], ['Pending', 'Not Configured'], true)
            )->count(),
            'compliant'     => $apps->filter(fn ($a) => ! $a['disqualified'] && $a['compliance_status'] === 'Pass')->count(),
            'non_compliant' => $apps->where('disqualified', true)->count()
                + $apps->filter(fn ($a) => ! $a['disqualified'] && $a['compliance_status'] === 'Fail')->count(),
            'shortlisted'   => $apps->where('shortlisted', true)->count(),
            'due_diligence' => $apps->filter(
                fn ($a) => in_array($a['due_diligence_status'], ['in_progress', 'completed', 'failed'], true)
            )->count(),
            'recommended'   => $apps->where('recommended', true)->count(),
        ];
    }
}
