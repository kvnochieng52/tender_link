<?php

namespace App\Http\Controllers;

use App\Models\InstitutionType;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Institutions/Index', [
            'institutions' => Institution::query()
                ->with('institutionType:id,name')
                ->where('created_by', auth()->id())
                ->select([
                    'id',
                    'institution_name',
                    'institution_type_id',
                    'email',
                    'telephone',
                    'logo',
                    'profile',
                    'website',
                    'address',
                    'created_at',
                ])
                ->latest()
                ->get(),
            'institutionTypes' => InstitutionType::query()
                ->where('active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_name'      => ['required', 'string', 'max:255'],
            'institution_type_id'   => ['required', 'exists:institution_types,id'],
            'institution_email'     => ['nullable', 'email', 'max:255'],
            'institution_telephone' => ['nullable', 'string', 'max:20'],
            'institution_website'   => ['nullable', 'url', 'max:255'],
            'institution_address'   => ['nullable', 'string', 'max:255'],
            'institution_profile'   => ['nullable', 'string'],
            'institution_logo'      => ['nullable', 'image', 'max:3072'],
        ]);

        $logoPath = null;

        if ($request->hasFile('institution_logo')) {
            $logoPath = $request->file('institution_logo')->store('institutions/logos', 'public');
        }

        Institution::create([
            'institution_name'    => $validated['institution_name'],
            'institution_type_id' => $validated['institution_type_id'],
            'email'               => $validated['institution_email'] ?? null,
            'telephone'           => $validated['institution_telephone'] ?? null,
            'website'             => $validated['institution_website'] ?? null,
            'address'             => $validated['institution_address'] ?? null,
            'profile'             => $validated['institution_profile'] ?? null,
            'logo'                => $logoPath,
            'created_by'          => auth()->id(),
            'updated_by'          => auth()->id(),
        ]);

        return redirect()->route('institutions.index')->with('success', 'Institution added successfully.');
    }

    public function update(Request $request, Institution $institution): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'institution_name'    => ['required', 'string', 'max:255'],
            'institution_type_id' => ['required', 'exists:institution_types,id'],
            'institution_email'   => ['nullable', 'email', 'max:255'],
            'institution_telephone' => ['nullable', 'string', 'max:20'],
            'institution_website' => ['nullable', 'url', 'max:255'],
            'institution_address' => ['nullable', 'string', 'max:255'],
            'institution_profile' => ['nullable', 'string'],
            'institution_logo'    => ['nullable', 'image', 'max:3072'],
        ]);

        $updateData = [
            'institution_name'    => $validated['institution_name'],
            'institution_type_id' => $validated['institution_type_id'],
            'email'               => $validated['institution_email'] ?? $institution->email,
            'telephone'           => $validated['institution_telephone'] ?? $institution->telephone,
            'website'             => $validated['institution_website'] ?? $institution->website,
            'address'             => $validated['institution_address'] ?? $institution->address,
            'profile'             => $validated['institution_profile'] ?? $institution->profile,
            'updated_by'          => auth()->id(),
        ];

        if ($request->hasFile('institution_logo')) {
            $updateData['logo'] = $request->file('institution_logo')
                ->store('institutions/logos', 'public');
        }

        $institution->update($updateData);

        $institution->load('institutionType:id,name');

        if (request()->expectsJson()) {
            return response()->json([
                'institution' => [
                    'id'                  => $institution->id,
                    'institution_name'    => $institution->institution_name,
                    'institution_type_id' => $institution->institution_type_id,
                    'institution_type'    => $institution->institutionType,
                    'email'               => $institution->email,
                    'telephone'           => $institution->telephone,
                    'website'             => $institution->website,
                    'address'             => $institution->address,
                    'profile'             => $institution->profile,
                    'logo'                => $institution->logo,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Institution updated successfully.');
    }
}
