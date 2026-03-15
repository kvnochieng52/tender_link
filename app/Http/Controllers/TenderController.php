<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenderStoreRequest;
use App\Http\Requests\TenderUpdateRequest;
use App\Models\County;
use App\Models\Industry;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tender;
use App\Models\TenderFile;
use App\Models\TenderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

class TenderController extends Controller
{
    public function publicShow(string $slug): Response
    {
        $tender = Tender::query()
            ->with([
                'institution:id,institution_name,institution_type_id,email,telephone,logo,profile,website,address',
                'institution.institutionType:id,name',
                'industry:id,name',
                'county:id,name',
                'status:id,name',
                'files:id,tender_id,file_name,filepath',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Tenders/PublicShow', [
            'tender' => $tender,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */

    public function index(Request $request): Response
    {
        $query = Tender::query()
            ->with([
                'institution:id,institution_name,logo',
                'industry:id,name',
                'county:id,name',
                'status:id,name',
                'files:id,tender_id,file_name,filepath',
            ])
            ->select([
                'id',
                'title',
                'slug',
                'tender_no',
                'institution_id',
                'industry_id',
                'county_id',
                'tender_status_id',
                'closing_date_and_time',
                'expiry_date',
                'created_at',
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tender_no', 'like', "%{$search}%");
            });
        }

        if ($statusId = $request->input('status_id')) {
            $query->where('tender_status_id', $statusId);
        }

        if ($industryId = $request->input('industry_id')) {
            $query->where('industry_id', $industryId);
        }

        if ($countyId = $request->input('county_id')) {
            $query->where('county_id', $countyId);
        }

        $tenders = $query->latest()->paginate(15)->withQueryString();

        $tenders->getCollection()->transform(function (Tender $tender) {
            $tender->encrypted_id = Crypt::encryptString((string) $tender->id);
            return $tender;
        });

        return Inertia::render('Tenders/Index', [
            'tenders'    => $tenders,
            'statuses'   => TenderStatus::select(['id', 'name'])->where('active', true)->get(),
            'industries' => Industry::select(['id', 'name'])->where('active', true)->orderBy('name')->get(),
            'counties'   => County::select(['id', 'name'])->where('active', true)->orderBy('name')->get(),
            'filters'    => $request->only(['search', 'status_id', 'industry_id', 'county_id']),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                              */
    /* ------------------------------------------------------------------ */

    public function create(): Response
    {
        return Inertia::render('Tenders/Create', [
            'institutions' => Institution::query()
                ->with(['institutionType:id,name'])
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
                ])
                ->orderBy('institution_name')
                ->get(),
            'institutionTypes' => InstitutionType::query()
                ->where('active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'industries' => Industry::query()
                ->where('active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'counties' => County::query()
                ->where('active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'statuses' => TenderStatus::where('active', true)->select(['id', 'name'])->get(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */

    public function store(TenderStoreRequest $request): RedirectResponse
    {
        $userId       = auth()->id();
        $activeStatus = TenderStatus::where('name', 'Active')->first();

        DB::transaction(function () use ($request, $userId, $activeStatus): void {
            $institutionId = $request->input('institution_id');

            if ($request->boolean('create_new_institution')) {
                $logoPath = null;

                if ($request->hasFile('institution_logo')) {
                    $logoPath = $request->file('institution_logo')->store('institutions/logos', 'public');
                }

                $institution = Institution::create([
                    'institution_name'    => $request->input('institution_name'),
                    'institution_type_id' => $request->input('institution_type_id'),
                    'email'               => $request->input('institution_email'),
                    'telephone'           => $request->input('institution_telephone'),
                    'logo'                => $logoPath,
                    'profile'             => $request->input('institution_profile'),
                    'website'             => $request->input('institution_website'),
                    'address'             => $request->input('institution_address'),
                    'created_by'          => $userId,
                    'updated_by'          => $userId,
                ]);

                $institutionId = $institution->id;
            } elseif ($request->boolean('edit_institution') && $institutionId) {
                $institution = Institution::findOrFail($institutionId);

                $updateData = [
                    'institution_name'    => $request->input('institution_name', $institution->institution_name),
                    'institution_type_id' => $request->input('institution_type_id', $institution->institution_type_id),
                    'email'               => $request->input('institution_email', $institution->email),
                    'telephone'           => $request->input('institution_telephone', $institution->telephone),
                    'profile'             => $request->input('institution_profile', $institution->profile),
                    'website'             => $request->input('institution_website', $institution->website),
                    'address'             => $request->input('institution_address', $institution->address),
                    'updated_by'          => $userId,
                ];

                if ($request->hasFile('institution_logo')) {
                    $updateData['logo'] = $request->file('institution_logo')->store('institutions/logos', 'public');
                }

                $institution->update($updateData);
            }

            $tender = Tender::create([
                'title'                 => $request->input('title'),
                'slug'                  => $this->generateUniqueSlug($request->input('title')),
                'tender_no'             => $request->input('tender_no'),
                'institution_id'        => $institutionId,
                'industry_id'           => $request->input('industry_id'),
                'county_id'             => $request->input('county_id'),
                'closing_date_and_time' => $request->input('closing_date_and_time'),
                'expiry_date'           => $request->input('expiry_date'),
                'tender_status_id'      => $activeStatus?->id,
                'description'           => $request->input('description'),
                'key_requirements'      => $request->input('key_requirements'),
                'created_by'            => $userId,
                'updated_by'            => $userId,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('tenders/files', 'public');

                    TenderFile::create([
                        'tender_id'  => $tender->id,
                        'filepath'   => $path,
                        'file_name'  => $uploadedFile->getClientOriginalName(),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }
        });

        return redirect()
            ->route('tenders.index')
            ->with('success', 'Tender registered successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */

    public function edit(string $encryptedId): Response
    {
        $id     = Crypt::decryptString($encryptedId);
        $tender = Tender::with(['files', 'institution.institutionType'])->findOrFail($id);

        return Inertia::render('Tenders/Edit', [
            'tender'           => $tender,
            'encryptedId'      => $encryptedId,
            'institutions'     => Institution::query()
                ->with(['institutionType:id,name'])
                ->select(['id', 'institution_name', 'institution_type_id', 'email', 'telephone', 'logo', 'profile', 'website', 'address'])
                ->orderBy('institution_name')
                ->get(),
            'institutionTypes' => InstitutionType::where('active', true)->select(['id', 'name'])->orderBy('name')->get(),
            'industries'       => Industry::where('active', true)->select(['id', 'name'])->orderBy('name')->get(),
            'counties'         => County::where('active', true)->select(['id', 'name'])->orderBy('name')->get(),
            'statuses'         => TenderStatus::where('active', true)->select(['id', 'name'])->get(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */

    public function update(TenderUpdateRequest $request, string $encryptedId): RedirectResponse
    {
        $id     = Crypt::decryptString($encryptedId);
        $tender = Tender::findOrFail($id);
        $userId = auth()->id();

        DB::transaction(function () use ($request, $tender, $userId): void {
            $institutionId = $request->input('institution_id');

            if ($request->boolean('create_new_institution')) {
                $logoPath = null;

                if ($request->hasFile('institution_logo')) {
                    $logoPath = $request->file('institution_logo')->store('institutions/logos', 'public');
                }

                $institution = Institution::create([
                    'institution_name'    => $request->input('institution_name'),
                    'institution_type_id' => $request->input('institution_type_id'),
                    'email'               => $request->input('institution_email'),
                    'telephone'           => $request->input('institution_telephone'),
                    'logo'                => $logoPath,
                    'profile'             => $request->input('institution_profile'),
                    'website'             => $request->input('institution_website'),
                    'address'             => $request->input('institution_address'),
                    'created_by'          => $userId,
                    'updated_by'          => $userId,
                ]);

                $institutionId = $institution->id;
            } elseif ($request->boolean('edit_institution') && $institutionId) {
                $institution = Institution::findOrFail($institutionId);

                $updateData = [
                    'institution_name'    => $request->input('institution_name', $institution->institution_name),
                    'institution_type_id' => $request->input('institution_type_id', $institution->institution_type_id),
                    'email'               => $request->input('institution_email', $institution->email),
                    'telephone'           => $request->input('institution_telephone', $institution->telephone),
                    'profile'             => $request->input('institution_profile', $institution->profile),
                    'website'             => $request->input('institution_website', $institution->website),
                    'address'             => $request->input('institution_address', $institution->address),
                    'updated_by'          => $userId,
                ];

                if ($request->hasFile('institution_logo')) {
                    $updateData['logo'] = $request->file('institution_logo')->store('institutions/logos', 'public');
                }

                $institution->update($updateData);
            }

            $tender->update([
                'title'                 => $request->input('title'),
                'tender_no'             => $request->input('tender_no'),
                'institution_id'        => $institutionId,
                'industry_id'           => $request->input('industry_id'),
                'county_id'             => $request->input('county_id'),
                'closing_date_and_time' => $request->input('closing_date_and_time'),
                'expiry_date'           => $request->input('expiry_date'),
                'tender_status_id'      => $request->input('tender_status_id') ?? $tender->tender_status_id,
                'description'           => $request->input('description'),
                'key_requirements'      => $request->input('key_requirements'),
                'updated_by'            => $userId,
            ]);

            // Remove files
            if ($request->filled('remove_file_ids')) {
                TenderFile::whereIn('id', $request->input('remove_file_ids'))
                    ->where('tender_id', $tender->id)
                    ->delete();
            }

            // New files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('tenders/files', 'public');

                    TenderFile::create([
                        'tender_id'  => $tender->id,
                        'filepath'   => $path,
                        'file_name'  => $uploadedFile->getClientOriginalName(),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }
        });

        return redirect()
            ->route('tenders.index')
            ->with('success', 'Tender updated successfully.');
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title, '-');

        if ($base === '') {
            $base = 'tender';
        }

        do {
            $candidate = $base . '-' . random_int(100000, 999999);
        } while (Tender::where('slug', $candidate)->exists());

        return $candidate;
    }
}
