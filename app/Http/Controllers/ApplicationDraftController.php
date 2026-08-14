<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDraft;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApplicationDraftController extends Controller
{
    /**
     * Save (upsert) the current user's draft for a tender.
     * Any requirement files or filled questionnaire currently sitting under
     * applications/temp/ are moved into application_drafts/{draft_id}/ so
     * they survive across sessions.
     */
    public function save(Request $request, string $slug)
    {
        $tender = Tender::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'data'         => ['required', 'array'],
            'current_step' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $draft = ApplicationDraft::firstOrNew([
            'user_id'   => Auth::id(),
            'tender_id' => $tender->id,
        ]);

        if (! $draft->exists) {
            // Persist first so we have an ID for the storage path.
            $draft->data         = [];
            $draft->current_step = $validated['current_step'] ?? 1;
            $draft->save();
        }

        $data = $this->relocateTempFilesToDraftDir($validated['data'], $draft);

        $draft->data         = $data;
        $draft->current_step = $validated['current_step'] ?? $draft->current_step;
        $draft->save();

        return response()->json([
            'success' => true,
            'draft'   => [
                'id'           => $draft->id,
                'data'         => $draft->data,
                'current_step' => $draft->current_step,
                'updated_at'   => $draft->updated_at,
            ],
        ]);
    }

    /**
     * Delete the user's draft (row + on-disk directory).
     */
    public function discard(Request $request, string $slug)
    {
        $tender = Tender::where('slug', $slug)->firstOrFail();

        $draft = ApplicationDraft::where('user_id', Auth::id())
            ->where('tender_id', $tender->id)
            ->first();

        if ($draft) {
            Storage::disk('public')->deleteDirectory($draft->storagePath());
            $draft->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Walk the draft data payload and move any file paths that still point
     * into applications/temp/ to the draft's own directory. Rewrites the
     * paths in-place so the returned draft always has stable, durable
     * locations.
     */
    private function relocateTempFilesToDraftDir(array $data, ApplicationDraft $draft): array
    {
        $draftDir = $draft->storagePath();

        // Requirement files: array keyed by index -> { filepath, name, ... }
        if (! empty($data['requirementFiles']) && is_array($data['requirementFiles'])) {
            foreach ($data['requirementFiles'] as $idx => $rf) {
                if (! is_array($rf) || empty($rf['filepath'])) {
                    continue;
                }

                $data['requirementFiles'][$idx]['filepath'] = $this->moveIfTemp(
                    $rf['filepath'],
                    $draftDir
                );
            }
        }

        // Filled questionnaire: single file
        if (! empty($data['filledQuestionnaire']) && is_array($data['filledQuestionnaire'])) {
            $fq = $data['filledQuestionnaire'];
            if (! empty($fq['filepath'])) {
                $data['filledQuestionnaire']['filepath'] = $this->moveIfTemp(
                    $fq['filepath'],
                    $draftDir . '/questionnaire'
                );
            }
        }

        return $data;
    }

    private function moveIfTemp(string $path, string $destDir): string
    {
        if (str_starts_with($path, 'applications/temp/') === false) {
            return $path;
        }

        if (! Storage::disk('public')->exists($path)) {
            return $path;
        }

        $newPath = $destDir . '/' . basename($path);

        // Ensure destination directory exists (Storage::move handles missing dirs)
        Storage::disk('public')->move($path, $newPath);

        return $newPath;
    }
}
