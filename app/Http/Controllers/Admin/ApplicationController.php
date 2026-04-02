<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\ApplicationProcessStatus;
use App\Models\ApplicationStatus;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function all(Request $request)
    {
        $query = Application::query()
            ->with(['tender:id,title,tender_no', 'files']);

        if ($q = $request->input('q')) {
            $query->where(function ($qr) use ($q) {
                $qr->where('company_name', 'like', "%{$q}%")
                    ->orWhere('representative_name', 'like', "%{$q}%")
                    ->orWhere('telephone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $apps = $query->latest()->paginate(25)->withQueryString();

        $apps->getCollection()->transform(function (Application $app) {
            $app->encrypted_id = Crypt::encryptString((string) $app->id);
            if ($app->tender) {
                $app->tender->encrypted_id = Crypt::encryptString((string) $app->tender->id);
            }
            return $app;
        });

        return Inertia::render('Admin/Applications/All', [
            'applications' => $apps,
            'filters'      => $request->only('q'),
        ]);
    }

    public function index(\Illuminate\Http\Request $request, string $encryptedId)
    {
        $id = Crypt::decryptString($encryptedId);
        $tender = Tender::select(['id', 'title', 'tender_no'])->findOrFail($id);

        $query = Application::query()->where('tender_id', $tender->id)->with(['files.requirement']);

        if ($q = $request->input('q')) {
            $query->where(function ($qr) use ($q) {
                $qr->where('company_name', 'like', "%{$q}%")
                    ->orWhere('representative_name', 'like', "%{$q}%")
                    ->orWhere('telephone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $apps = $query->latest()->paginate(20)->withQueryString();

        $apps->getCollection()->transform(function (Application $app) {
            $app->encrypted_id = Crypt::encryptString((string) $app->id);
            return $app;
        });

        return Inertia::render('Admin/Applications/Index', [
            'tender' => $tender,
            'applications' => $apps,
            'filters' => $request->only('q'),
        ]);
    }

    public function show(string $encryptedAppId)
    {
        $id = Crypt::decryptString($encryptedAppId);
        $app = Application::with(['files.requirement', 'tender'])->findOrFail($id);

        if ($app->tender) {
            $app->tender->encrypted_id = Crypt::encryptString((string) $app->tender->id);
        }

        // compute stats: total required (mandatory) and submitted counts
        $requiredCount = 0;
        $submittedCount = $app->files()->count();
        $submittedRequiredCount = 0;
        $missingRequired = [];

        if ($app->tender) {
            $requiredCount = $app->tender->requirements()->where('mandatory', true)->count();

            // build set of tender_requirement_ids submitted
            $submittedReqIds = $app->files->pluck('tender_requirement_id')->filter()->unique()->toArray();

            // count how many mandatory requirements are satisfied
            $mandatoryReqs = $app->tender->requirements()->where('mandatory', true)->get();
            foreach ($mandatoryReqs as $mr) {
                if (in_array($mr->id, $submittedReqIds)) {
                    $submittedRequiredCount++;
                } else {
                    $missingRequired[] = $mr->title;
                }
            }
        }

        // resolve county name if present
        $countyName = null;
        if ($app->county_id) {
            $countyModel = \App\Models\County::find($app->county_id);
            $countyName = $countyModel?->name;
        }

        // attach computed attributes to application for convenience
        $app->stats = [
            'required_total' => $requiredCount,
            'submitted_total' => $submittedCount,
            'submitted_required' => $submittedRequiredCount,
            'missing_required_count' => count($missingRequired),
            'missing_required_titles' => $missingRequired,
        ];
        $app->county_name = $countyName;

        return Inertia::render('Admin/Applications/Show', [
            'application' => $app,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // EVALUATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Main evaluation dashboard for a tender's applications.
     */
    public function evaluate(Request $request, string $encryptedId)
    {
        $id = Crypt::decryptString($encryptedId);

        $tender = Tender::with([
            'status:id,name',
            'applicationProcessStatus:id,name,color',
            'requirements:id,tender_id,title,mandatory',
            'institution:id,institution_name',
        ])->findOrFail($id);

        $tender->encrypted_id = $encryptedId;

        // Mandatory requirements count for this tender
        $mandatoryCount = $tender->requirements->where('mandatory', true)->count();
        $totalReqCount  = $tender->requirements->count();

        $query = Application::query()
            ->where('tender_id', $tender->id)
            ->with([
                'applicationStatus:id,name,color',
                'files:id,application_id,tender_requirement_id,file_name,filepath',
                'files.requirement:id,title,mandatory',
                'notes:id,application_id,user_id,note,created_at',
                'notes.user:id,name',
            ]);

        if ($statusFilter = $request->input('status_id')) {
            $query->where('application_status_id', $statusFilter);
        }
        if ($q = $request->input('q')) {
            $query->where(function ($qr) use ($q) {
                $qr->where('company_name', 'like', "%{$q}%")
                    ->orWhere('representative_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $apps = $query->latest()->get();

        // Compute compliance for each application
        $mandatoryReqIds = $tender->requirements->where('mandatory', true)->pluck('id')->toArray();

        $apps = $apps->map(function (Application $app) use ($mandatoryCount, $mandatoryReqIds, $totalReqCount) {
            $app->encrypted_id = Crypt::encryptString((string) $app->id);

            // Resolve county name for modal display
            $app->county_name = $app->county_id
                ? \App\Models\County::find($app->county_id)?->name
                : null;

            $submittedReqIds     = $app->files->pluck('tender_requirement_id')->filter()->unique()->toArray();
            $satisfiedMandatory  = count(array_intersect($mandatoryReqIds, $submittedReqIds));
            $missingMandatory    = array_values(array_diff($mandatoryReqIds, $submittedReqIds));

            $app->compliance = [
                'mandatory_total'     => $mandatoryCount,
                'mandatory_satisfied' => $satisfiedMandatory,
                'total_docs'          => $totalReqCount,
                'docs_submitted'      => $app->files->count(),
                'is_complete'         => $mandatoryCount === 0 || $satisfiedMandatory === $mandatoryCount,
                'missing_req_ids'     => $missingMandatory,
                'percent'             => $mandatoryCount > 0
                    ? round(($satisfiedMandatory / $mandatoryCount) * 100)
                    : 100,
            ];

            return $app;
        });

        // Summary stats
        $stats = [
            'total'       => $apps->count(),
            'pending'     => $apps->where('application_status_id', null)->count()
                + $apps->filter(fn($a) => $a->applicationStatus?->name === 'Pending')->count(),
            'shortlisted' => $apps->filter(fn($a) => $a->applicationStatus?->name === 'Shortlisted')->count(),
            'rejected'    => $apps->filter(fn($a) => $a->applicationStatus?->name === 'Rejected')->count(),
            'confirmed'   => $apps->filter(fn($a) => $a->applicationStatus?->name === 'Confirmed')->count(),
            'complete'    => $apps->filter(fn($a) => $a->compliance['is_complete'])->count(),
        ];

        return Inertia::render('Admin/Applications/Evaluate', [
            'tender'             => $tender,
            'applications'       => $apps->values(),
            'stats'              => $stats,
            'applicationStatuses' => ApplicationStatus::select(['id', 'name', 'color'])->get(),
            'processStatuses'    => ApplicationProcessStatus::orderBy('order')->select(['id', 'name', 'color'])->get(),
            'filters'            => $request->only(['q', 'status_id']),
        ]);
    }

    /**
     * Update individual application status (shortlist / reject / confirm / pending).
     */
    public function updateApplicationStatus(Request $request, string $encryptedAppId)
    {
        $request->validate([
            'application_status_id' => ['nullable', 'exists:application_statuses,id'],
        ]);

        $id  = Crypt::decryptString($encryptedAppId);
        $app = Application::findOrFail($id);
        $app->update(['application_status_id' => $request->input('application_status_id')]);

        return back()->with('success', 'Application status updated.');
    }

    /**
     * Update rating and evaluation notes for an application.
     */
    public function updateApplicationRating(Request $request, string $encryptedAppId)
    {
        $request->validate([
            'rating' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $id  = Crypt::decryptString($encryptedAppId);
        $app = Application::findOrFail($id);
        $app->update($request->only(['rating']));

        return back()->with('success', 'Score saved.');
    }

    /**
     * Add a CRM-style note to an application.
     */
    public function addNote(Request $request, string $encryptedAppId)
    {
        $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $id  = Crypt::decryptString($encryptedAppId);
        Application::findOrFail($id); // ensure it exists

        ApplicationNote::create([
            'application_id' => $id,
            'user_id'        => auth()->id(),
            'note'           => $request->input('note'),
        ]);

        return back()->with('success', 'Note added.');
    }

    /**
     * Update the tender's application process status.
     */
    public function updateTenderProcessStatus(Request $request, string $encryptedId)
    {
        $request->validate([
            'application_process_status_id' => ['nullable', 'exists:application_process_statuses,id'],
        ]);

        $id     = Crypt::decryptString($encryptedId);
        $tender = Tender::findOrFail($id);
        $tender->update([
            'application_process_status_id' => $request->input('application_process_status_id'),
        ]);

        return back()->with('success', 'Process status updated.');
    }
}
