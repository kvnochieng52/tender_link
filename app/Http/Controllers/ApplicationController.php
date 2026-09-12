<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationSubmittedMail;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\ApplicationFile;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    // Upload a single requirement file to temp storage and return path
    public function uploadTempFile(Request $request, string $slug)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'requirement_id' => ['nullable', 'integer']
        ]);

        $tender = Tender::where('slug', $slug)->firstOrFail();

        $file = $request->file('file');
        $path = $file->store('applications/temp', 'public');

        return response()->json([
            'success' => true,
            'filepath' => $path,
            'file_name' => $file->getClientOriginalName(),
        ]);
    }

    // Delete a previously uploaded temp file
    public function deleteTempFile(Request $request, string $slug)
    {
        $request->validate([
            'filepath' => ['required', 'string'],
        ]);

        $path = $request->input('filepath');
        if (strpos($path, 'applications/temp') === false) {
            return response()->json(['success' => false, 'message' => 'Invalid path'], 400);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['success' => true]);
    }

    // Store the full application and move files from temp to permanent location
    public function store(Request $request, string $slug)
    {
        $tender = Tender::where('slug', $slug)->firstOrFail();

        // Filled questionnaire is mandatory when the tender ships a CBQ template.
        // Accept EITHER a fresh upload OR a stored path from a resumed draft.
        $questionnaireRequired = (bool) $tender->confidential_questionnaire_file_path;

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'county_id' => ['nullable', 'integer'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'representative_position' => ['nullable', 'string', 'max:255'],
            'representative_telephone' => ['nullable', 'string', 'max:50'],
            'representative_email' => ['nullable', 'email', 'max:255'],
            'additional_notes' => ['nullable', 'string'],
            'requirement_titles' => ['nullable', 'array'],
            'requirement_file_paths' => ['nullable', 'array'],
            'requirement_file_requirement_ids' => ['nullable', 'array'],
            'tender_category_ids' => ['nullable', 'array'],
            'tender_category_ids.*' => ['integer', 'exists:tender_categories,id'],
            'filled_questionnaire_file' => ['nullable', 'file', 'max:10240'],
            'filled_questionnaire_file_path' => ['nullable', 'string'],
            'filled_questionnaire_file_name' => ['nullable', 'string'],
            'bid_amount' => ['nullable', 'numeric', 'min:0'],
            'disclaimer_accepted' => ['required', 'accepted'],
        ], [
            'disclaimer_accepted.required' => 'You must acknowledge the disclaimer before submitting.',
            'disclaimer_accepted.accepted' => 'You must acknowledge the disclaimer before submitting.',
        ]);

        // Enforce presence when the tender requires a CBQ.
        $hasQuestionnaireUpload = $request->hasFile('filled_questionnaire_file');
        $hasQuestionnairePath   = (bool) $request->input('filled_questionnaire_file_path');
        if ($questionnaireRequired && ! $hasQuestionnaireUpload && ! $hasQuestionnairePath) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload your filled Confidential Business Questionnaire.',
                'errors'  => [
                    'filled_questionnaire_file' => ['Please upload your filled Confidential Business Questionnaire.'],
                ],
            ], 422);
        }

        // Resolve which categories the user is applying for.
        // If the tender has categories, at least one must be selected.
        $categoryIds = collect($request->input('tender_category_ids', []))
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        if ($tender->categories()->exists() && $categoryIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one category to apply for.',
            ], 422);
        }

        // Only accept category IDs that actually belong to this tender.
        if ($categoryIds->isNotEmpty()) {
            $categoryIds = $tender->categories()
                ->whereIn('id', $categoryIds)
                ->pluck('id');
        }

        // Build one application per selected category, or a single application
        // (no category) if this tender is not categorised.
        $targets = $categoryIds->isEmpty() ? [null] : $categoryIds->all();

        $applicantPayload = [
            'tender_id' => $tender->id,
            'user_id'   => Auth::id(),
            'company_name' => $validated['company_name'],
            'telephone' => $validated['telephone'] ?? null,
            'website' => $validated['website'] ?? null,
            'county_id' => $validated['county_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'email' => $validated['email'] ?? null,
            'representative_name' => $validated['representative_name'] ?? null,
            'representative_position' => $validated['representative_position'] ?? null,
            'representative_telephone' => $validated['representative_telephone'] ?? null,
            'representative_email' => $validated['representative_email'] ?? null,
            'additional_notes' => $validated['additional_notes'] ?? null,
            'bid_amount' => $validated['bid_amount'] ?? null,
            'disclaimer_accepted_at' => now(),
        ];

        $paths = $request->input('requirement_file_paths', []);
        $reqIds = $request->input('requirement_file_requirement_ids', []);
        $origNames = $request->input('requirement_file_original_names', []);

        // Filled questionnaire can arrive as either a fresh upload OR a path
        // pointing into the resumed draft's directory.
        $questionnaireUpload   = $request->file('filled_questionnaire_file');
        $questionnairePath     = $request->input('filled_questionnaire_file_path');
        $questionnaireOrigName = $questionnaireUpload
            ? $questionnaireUpload->getClientOriginalName()
            : ($request->input('filled_questionnaire_file_name')
                ?: ($questionnairePath ? basename($questionnairePath) : null));

        $createdApplications = [];
        $lastTargetIndex = count($targets) - 1;

        // Compute the next per-tender sequence for application_no, transactionally.
        // We parse existing application_no values to find the max seq and use it
        // as the starting point so gaps left by unsubmitted rows never collide.
        $tenderYear = $tender->created_at?->format('Y') ?? date('Y');
        $nextSeq = 1;
        DB::transaction(function () use ($tender, &$nextSeq) {
            $existing = Application::where('tender_id', $tender->id)
                ->whereNotNull('application_no')
                ->lockForUpdate()
                ->pluck('application_no');

            $maxSeq = 0;
            foreach ($existing as $ref) {
                // Grab the trailing digits after the last hyphen.
                if (preg_match('/-(\d+)$/', (string) $ref, $m)) {
                    $maxSeq = max($maxSeq, (int) $m[1]);
                }
            }
            $nextSeq = $maxSeq + 1;
        });

        foreach ($targets as $t => $categoryId) {
            $applicationNo = sprintf(
                'TP/%s/%03d-APP-%03d',
                $tenderYear,
                $tender->id,
                $nextSeq++,
            );

            $application = Application::create($applicantPayload + [
                'tender_category_id' => $categoryId,
                'application_no'     => $applicationNo,
            ]);
            $createdApplications[] = $application;

            $isLastTarget = ($t === $lastTargetIndex);

            foreach ($paths as $i => $tempPath) {
                if (!$tempPath) continue;

                $filename = basename($tempPath);
                $newPath = 'applications/' . $application->id . '/' . $filename;

                if (Storage::disk('public')->exists($tempPath)) {
                    // For all-but-last applications, copy the temp file so subsequent
                    // iterations still have the source. For the last one, move to
                    // release temp storage.
                    if ($isLastTarget) {
                        Storage::disk('public')->move($tempPath, $newPath);
                    } else {
                        Storage::disk('public')->copy($tempPath, $newPath);
                    }
                } else {
                    // If the file was directly posted (unlikely here), fall back to path as-is.
                    $newPath = $tempPath;
                }

                ApplicationFile::create([
                    'application_id' => $application->id,
                    'tender_requirement_id' => $reqIds[$i] ?? null,
                    'file_name' => ($origNames[$i] ?? null) ?: $filename,
                    'filepath' => $newPath,
                ]);
            }

            // Filled Confidential Business Questionnaire: store a copy per application.
            if ($questionnaireUpload) {
                // Fresh upload: storeAs safely reads the PHP tmp file each call.
                $qPath = $questionnaireUpload->storeAs(
                    'applications/' . $application->id . '/questionnaire',
                    $questionnaireOrigName,
                    'public'
                );

                $application->update([
                    'filled_questionnaire_file_path' => $qPath,
                    'filled_questionnaire_file_name' => $questionnaireOrigName,
                ]);
            } elseif ($questionnairePath && Storage::disk('public')->exists($questionnairePath)) {
                // Resumed draft: copy for N-1 applications, move for the last so
                // the draft-owned file eventually leaves the drafts directory.
                $qName    = $questionnaireOrigName ?: basename($questionnairePath);
                $qDestDir = 'applications/' . $application->id . '/questionnaire';
                $qDest    = $qDestDir . '/' . $qName;

                if ($isLastTarget) {
                    Storage::disk('public')->move($questionnairePath, $qDest);
                } else {
                    Storage::disk('public')->copy($questionnairePath, $qDest);
                }

                $application->update([
                    'filled_questionnaire_file_path' => $qDest,
                    'filled_questionnaire_file_name' => $qName,
                ]);
            }
        }

        // Any draft for this (user, tender) is now redundant.
        $draft = ApplicationDraft::where('user_id', Auth::id())
            ->where('tender_id', $tender->id)
            ->first();
        if ($draft) {
            Storage::disk('public')->deleteDirectory($draft->storagePath());
            $draft->delete();
        }

        // Email the tenderer a summary of what was submitted. Emails failing
        // must not fail the submission — log and continue.
        try {
            $user = $request->user();
            if ($user && $user->email && count($createdApplications)) {
                // Eager-load relationships the mailable/blade will read.
                $eager = Application::with(['tenderCategory:id,tender_id,tender_no,title', 'files:id,application_id,file_name,filepath'])
                    ->whereIn('id', collect($createdApplications)->pluck('id'))
                    ->orderBy('id')
                    ->get()
                    ->all();

                Mail::to($user->email)->send(new ApplicationSubmittedMail($user, $tender, $eager));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed sending application submission email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => count($createdApplications) > 1
                ? count($createdApplications) . ' applications submitted.'
                : 'Application submitted.',
            'count'   => count($createdApplications),
        ]);
    }

    /**
     * Withdraw a submitted application (or a whole submission batch) back
     * into an editable draft, as long as the tender's closing date hasn't
     * passed. Files from the submitted applications are moved into the new
     * draft's directory. All previously-submitted rows for the same
     * (user, tender) are deleted.
     */
    public function unsubmit(Request $request, int $id)
    {
        $application = Application::with(['tender', 'tenderCategory', 'files'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $tender = $application->tender;

        if (! $tender) {
            return response()->json(['success' => false, 'message' => 'Tender not found.'], 404);
        }

        if ($tender->closing_date_and_time && $tender->closing_date_and_time->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'This tender has already closed. You can no longer amend your submission.',
            ], 422);
        }

        // All rows for this (user, tender) are treated as one submission batch.
        $siblings = Application::with(['tenderCategory', 'files'])
            ->where('user_id', Auth::id())
            ->where('tender_id', $tender->id)
            ->get();

        if ($siblings->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Nothing to unsubmit.'], 404);
        }

        // Use the first row as the applicant-details donor. Files are identical
        // copies across siblings by construction, so we take them from the same row.
        $donor = $siblings->first();

        $draft = ApplicationDraft::firstOrNew([
            'user_id'   => Auth::id(),
            'tender_id' => $tender->id,
        ]);
        if (! $draft->exists) {
            $draft->data         = [];
            $draft->current_step = 1;
            $draft->save();
        }

        $draftDir = $draft->storagePath();

        // Move requirement files from the donor's application directory into
        // the draft directory (only for the donor — the other siblings hold
        // duplicates, they'll be discarded when we delete the rows).
        $requirementFiles = [];
        foreach ($donor->files as $idx => $file) {
            $dest = $draftDir . '/' . basename($file->filepath);
            if (Storage::disk('public')->exists($file->filepath)) {
                Storage::disk('public')->move($file->filepath, $dest);
            }
            $requirementFiles[$idx] = [
                'filepath'       => $dest,
                'name'           => $file->file_name,
                'requirement_id' => $file->tender_requirement_id,
                'uploaded'       => true,
            ];
        }

        // Filled questionnaire (donor's copy).
        $filledQuestionnaire = null;
        if ($donor->filled_questionnaire_file_path) {
            $qDest = $draftDir . '/questionnaire/' . basename($donor->filled_questionnaire_file_path);
            if (Storage::disk('public')->exists($donor->filled_questionnaire_file_path)) {
                Storage::disk('public')->move($donor->filled_questionnaire_file_path, $qDest);
            }
            $filledQuestionnaire = [
                'filepath' => $qDest,
                'name'     => $donor->filled_questionnaire_file_name ?: basename($qDest),
            ];
        }

        // Collapse back into a single draft payload.
        $draft->data = [
            'applicant' => [
                'company_name'     => $donor->company_name,
                'telephone'        => $donor->telephone,
                'website'          => $donor->website,
                'county'           => $donor->county_id,
                'address'          => $donor->address,
                'email'            => $donor->email,
                'additional_notes' => $donor->additional_notes,
            ],
            'representative' => [
                'full_name' => $donor->representative_name,
                'position'  => $donor->representative_position,
                'telephone' => $donor->representative_telephone,
                'email'     => $donor->representative_email,
            ],
            'selectedCategoryIds' => $siblings
                ->pluck('tender_category_id')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'requirementFiles'    => $requirementFiles,
            'filledQuestionnaire' => $filledQuestionnaire,
        ];
        $draft->current_step = 1;
        $draft->last_reminder_sent_at = null;
        $draft->save();

        // Delete every submitted row + on-disk residue.
        foreach ($siblings as $s) {
            $appDir = 'applications/' . $s->id;
            Storage::disk('public')->deleteDirectory($appDir);
            $s->files()->delete();
            $s->notes()->delete();
            $s->delete();
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Your submission has been withdrawn. You can now amend it and re-submit before the deadline.',
            'redirect_url' => route('tenders.public.show', ['slug' => $tender->slug]),
        ]);
    }

    public function myApplications(Request $request)
    {
        $query = Application::query()
            ->where('user_id', Auth::id())
            ->with([
                'tender:id,title,tender_no,slug,closing_date_and_time',
                'tenderCategory:id,tender_id,tender_no,title',
                'files',
            ]);

        if ($q = $request->input('q')) {
            $query->where(function ($qr) use ($q) {
                $qr->where('company_name', 'like', "%{$q}%")
                    ->orWhereHas('tender', fn($t) => $t->where('title', 'like', "%{$q}%")
                        ->orWhere('tender_no', 'like', "%{$q}%"));
            });
        }

        $apps = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Applications/MyApplications', [
            'applications' => $apps,
            'filters'      => $request->only('q'),
        ]);
    }
}
