<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

        $application = Application::create([
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
        ]);

        $paths = $request->input('requirement_file_paths', []);
        $reqIds = $request->input('requirement_file_requirement_ids', []);
        $origNames = $request->input('requirement_file_original_names', []);
        foreach ($paths as $i => $tempPath) {
            if (!$tempPath) continue;
            // Move file from temp to permanent folder for this application
            $filename = basename($tempPath);
            $newPath = 'applications/' . $application->id . '/' . $filename;
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->move($tempPath, $newPath);
            } else {
                // if file was directly posted, skip
                $newPath = $tempPath;
            }

            $storedOriginal = $origNames[$i] ?? null;
            ApplicationFile::create([
                'application_id' => $application->id,
                'tender_requirement_id' => $reqIds[$i] ?? null,
                'file_name' => $storedOriginal ?: $filename,
                'filepath' => $newPath,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Application submitted']);
    }

    public function myApplications(Request $request)
    {
        $query = Application::query()
            ->where('user_id', Auth::id())
            ->with(['tender:id,title,tender_no,slug,closing_date_and_time', 'files']);

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
