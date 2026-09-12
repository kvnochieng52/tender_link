<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\TenderActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TenderApplicationActionsController extends Controller
{
    public function toggleShortlist(Request $request, string $encryptedId, int $applicationId): RedirectResponse
    {
        $tenderId = (int) Crypt::decryptString($encryptedId);
        $app = Application::where('tender_id', $tenderId)->findOrFail($applicationId);

        $shortlistedId = ApplicationStatus::where('name', 'Shortlisted')->value('id');
        if (! $shortlistedId) {
            return back()->withErrors(['shortlist' => 'Shortlisted status not configured.']);
        }

        $isShortlisted = $app->application_status_id === $shortlistedId;
        $label = ($app->application_no ? "{$app->application_no} " : '') . $app->company_name;

        if ($isShortlisted) {
            // Un-shortlist: revert to Pending.
            $pendingId = ApplicationStatus::where('name', 'Pending')->value('id');
            $app->update(['application_status_id' => $pendingId]);

            TenderActivityLog::record(
                $tenderId,
                $request->user()?->id,
                'shortlist.removed',
                "Removed {$label} from the shortlist",
                Application::class,
                $app->id,
            );
        } else {
            $app->update(['application_status_id' => $shortlistedId]);

            TenderActivityLog::record(
                $tenderId,
                $request->user()?->id,
                'shortlist.added',
                "Shortlisted {$label}",
                Application::class,
                $app->id,
            );
        }

        return back()->with('success', $isShortlisted ? 'Removed from shortlist.' : 'Added to shortlist.');
    }

    public function saveDueDiligence(Request $request, string $encryptedId, int $applicationId): RedirectResponse
    {
        $tenderId = (int) Crypt::decryptString($encryptedId);
        $app = Application::where('tender_id', $tenderId)->findOrFail($applicationId);

        $data = $request->validate([
            'due_diligence_status' => ['required', 'in:pending,in_progress,completed,failed'],
            'due_diligence_notes'  => ['nullable', 'string'],
        ]);

        $update = [
            'due_diligence_status' => $data['due_diligence_status'],
            'due_diligence_notes'  => $data['due_diligence_notes'] ?? null,
        ];
        if (in_array($data['due_diligence_status'], ['completed', 'failed'], true)) {
            $update['due_diligence_completed_at'] = now();
        } else {
            $update['due_diligence_completed_at'] = null;
        }

        $app->update($update);

        $label = ($app->application_no ? "{$app->application_no} " : '') . $app->company_name;
        TenderActivityLog::record(
            $tenderId,
            $request->user()?->id,
            'due_diligence.updated',
            "Due diligence for {$label} set to {$data['due_diligence_status']}",
            Application::class,
            $app->id,
        );

        return back()->with('success', 'Due diligence updated.');
    }

    public function disqualify(Request $request, string $encryptedId, int $applicationId): RedirectResponse
    {
        $tenderId = (int) Crypt::decryptString($encryptedId);
        $app = Application::where('tender_id', $tenderId)->findOrFail($applicationId);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $rejectedId = ApplicationStatus::where('name', 'Rejected')->value('id');
        if (! $rejectedId) {
            return back()->withErrors(['reason' => 'Rejected status not configured.']);
        }

        $app->update([
            'application_status_id' => $rejectedId,
            'evaluation_notes'      => $data['reason'],
        ]);

        // Record the reason as a communication note too so it shows on the
        // application timeline and the Communications tab.
        \App\Models\ApplicationNote::create([
            'application_id' => $app->id,
            'user_id'        => $request->user()?->id,
            'note'           => "Disqualified: {$data['reason']}",
        ]);

        $label = ($app->application_no ? "{$app->application_no} " : '') . $app->company_name;
        TenderActivityLog::record(
            $tenderId,
            $request->user()?->id,
            'application.disqualified',
            "Disqualified {$label}: {$data['reason']}",
            Application::class,
            $app->id,
        );

        return back()->with('success', 'Bidder disqualified.');
    }

    public function saveRecommendation(Request $request, string $encryptedId, int $applicationId): RedirectResponse
    {
        $tenderId = (int) Crypt::decryptString($encryptedId);
        $app = Application::where('tender_id', $tenderId)->findOrFail($applicationId);

        $data = $request->validate([
            'recommended'         => ['required', 'boolean'],
            'recommendation_note' => ['nullable', 'string'],
        ]);

        $app->update([
            'recommended_at'      => $data['recommended'] ? now() : null,
            'recommendation_note' => $data['recommendation_note'] ?? null,
        ]);

        TenderActivityLog::record(
            $tenderId,
            $request->user()?->id,
            $data['recommended'] ? 'recommendation.added' : 'recommendation.removed',
            ($data['recommended'] ? 'Recommended ' : 'Un-recommended ') . ($app->application_no ? "{$app->application_no} " : '') . $app->company_name,
            Application::class,
            $app->id,
        );

        return back()->with('success', 'Recommendation updated.');
    }
}
