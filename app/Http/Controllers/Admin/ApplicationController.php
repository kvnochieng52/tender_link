<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class ApplicationController extends Controller
{
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
}
