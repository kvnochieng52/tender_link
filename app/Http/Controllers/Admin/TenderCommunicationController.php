<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TenderCommunicationMail;
use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\Tender;
use App\Models\TenderActivityLog;
use App\Models\TenderCommunication;
use App\Services\CommunicationTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TenderCommunicationController extends Controller
{
    /**
     * Send a communication to one or more bidders on this tender.
     *
     * recipients:
     *   all           — every application on the tender
     *   shortlisted   — every application with status Shortlisted
     *   recommended   — every application with recommended_at set
     *   awarded       — the awarded application (if any)
     *   specific      — a single application (application_id required)
     */
    public function send(Request $request, string $encryptedId): RedirectResponse
    {
        $tender = Tender::with('institution:id,institution_name')
            ->findOrFail(Crypt::decryptString($encryptedId));

        $data = $request->validate([
            'category'       => ['required', 'string', 'in:' . implode(',', array_keys(CommunicationTemplates::all()))],
            'recipients'     => ['required', 'in:all,shortlisted,recommended,awarded,specific'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'subject'        => ['required', 'string', 'max:255'],
            'body'           => ['required', 'string'],
        ]);

        // Resolve the recipient list.
        $appQuery = Application::query()
            ->where('tender_id', $tender->id)
            ->with(['user:id,name,email']);

        switch ($data['recipients']) {
            case 'shortlisted':
                $shortId = ApplicationStatus::where('name', 'Shortlisted')->value('id');
                if ($shortId) {
                    $appQuery->where('application_status_id', $shortId);
                }
                break;
            case 'recommended':
                $appQuery->whereNotNull('recommended_at');
                break;
            case 'awarded':
                $awardedAppId = \App\Models\TenderAward::where('tender_id', $tender->id)->value('application_id');
                if ($awardedAppId) {
                    $appQuery->where('id', $awardedAppId);
                } else {
                    $appQuery->whereRaw('1 = 0');
                }
                break;
            case 'specific':
                if (empty($data['application_id'])) {
                    return back()->withErrors(['application_id' => 'Please pick a bidder for a specific-recipient send.']);
                }
                $appQuery->where('id', $data['application_id']);
                break;
            case 'all':
            default:
                // no extra filter
                break;
        }

        $recipients = $appQuery->get();

        if ($recipients->isEmpty()) {
            return back()->withErrors(['recipients' => 'No recipients matched the selected group.']);
        }

        $sent   = 0;
        $failed = 0;

        foreach ($recipients as $app) {
            // Resolve email address (prefer application-level email, fall back to bidder user).
            $email = $app->email ?: $app->user?->email;
            if (! $email) {
                $failed++;
                continue;
            }

            // Per-recipient template substitution.
            $subject = CommunicationTemplates::substitute($data['subject'], $tender, $app);
            $body    = CommunicationTemplates::substitute($data['body'], $tender, $app);

            $comm = TenderCommunication::create([
                'tender_id'       => $tender->id,
                'application_id'  => $app->id,
                'category'        => $data['category'],
                'subject'         => $subject,
                'body'            => $body,
                'recipient_email' => $email,
                'recipient_name'  => $app->company_name ?: $app->user?->name,
                'sent_by'         => $request->user()?->id,
            ]);

            try {
                Mail::to($email)->send(new TenderCommunicationMail($comm));
                $comm->update(['sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                $comm->update(['error' => substr($e->getMessage(), 0, 500)]);
                Log::warning('TenderCommunication send failed', [
                    'communication_id' => $comm->id,
                    'error'            => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $categoryLabel = TenderCommunication::categoryLabels()[$data['category']] ?? $data['category'];
        TenderActivityLog::record(
            $tender->id,
            $request->user()?->id,
            'communication.sent',
            "Sent {$categoryLabel} to {$sent} recipient(s)"
                . ($failed ? " ({$failed} failed)" : ''),
        );

        if ($failed && ! $sent) {
            return back()->withErrors(['recipients' => "Delivery failed for all {$failed} recipient(s). Check the mail configuration."]);
        }

        return back()->with(
            'success',
            "Sent to {$sent} recipient(s)" . ($failed ? " · {$failed} failed" : '') . '.'
        );
    }
}
