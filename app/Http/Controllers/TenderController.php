<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenderStoreRequest;
use App\Http\Requests\TenderUpdateRequest;
use App\Jobs\SendTenderNotificationJob;
use App\Models\County;
use App\Models\CommonRequirement;
use App\Models\Industry;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Plan;
use App\Models\ApplicationDraft;
use App\Models\Tender;
use App\Models\TenderCategory;
use App\Models\TenderFile;
use App\Models\TenderRequirement;
use App\Models\TenderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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
                'requirements:id,tender_id,title,notes,mandatory,source,source_id',
                'categories:id,tender_id,tender_no,title,position',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        // Determine if the authenticated user has access to the full tender details
        $hasAccess = false;
        $user      = auth()->user();

        if ($user) {
            if ($tender->tender_link_process) {
                // Tender-specific fee: always require a payment record
                $hasAccess = $user->hasPaidForTender($tender->id);
            } else {
                // General subscription plan required
                $hasAccess = $user->hasActivePlan();
            }
        }

        $plans = Plan::where('is_active', true)->orderBy('amount')->get();

        $draft = $user
            ? ApplicationDraft::where('user_id', $user->id)
                ->where('tender_id', $tender->id)
                ->first()
            : null;

        return Inertia::render('Tenders/PublicShow', [
            'tender'          => $tender,
            'hasAccess'       => $hasAccess,
            'plans'           => $plans,
            'counties'        => County::query()->where('active', true)->select(['id', 'name'])->orderBy('name')->get(),
            'tendererProfile' => $user?->tendererProfile,
            'draft'           => $draft ? [
                'id'           => $draft->id,
                'data'         => $draft->data,
                'current_step' => $draft->current_step,
                'updated_at'   => $draft->updated_at,
            ] : null,
            'currentUrl'      => request()->url(),
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
                'applicationProcessStatus:id,name,color',
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
                'application_process_status_id',
                'tender_link_process',
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

        $tenders = $query->latest()->paginate(10)->withQueryString();

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

    /**
     * Public-facing search results page.
     */
    public function publicSearch(Request $request): Response
    {
        $query = Tender::query()
            ->open()
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

        $tenders = $query->latest()->paginate(10)->withQueryString();

        $tenders->getCollection()->transform(function (Tender $tender) {
            return $tender;
        });

        return Inertia::render('Tenders/PublicIndex', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'tenders'    => $tenders,
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
            'commonRequirements' => CommonRequirement::select(['id', 'title', 'notes', 'mandatory'])->orderBy('title')->get(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */

    public function store(TenderStoreRequest $request): RedirectResponse
    {
        $userId       = auth()->id();
        $activeStatus = TenderStatus::where('name', 'Active')->first();
        $tender = null;

        DB::transaction(function () use ($request, $userId, $activeStatus, &$tender): void {
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

            $advertPath = null;
            $advertName = null;
            if ($request->hasFile('advert_file')) {
                $advertFile = $request->file('advert_file');
                $advertPath = $advertFile->store('tenders/adverts', 'public');
                $advertName = $advertFile->getClientOriginalName();
            }

            $selfDeclarationPath = null;
            $selfDeclarationName = null;
            if ($request->hasFile('self_declaration_file')) {
                $selfDeclarationFile = $request->file('self_declaration_file');
                $selfDeclarationPath = $selfDeclarationFile->store('tenders/self_declarations', 'public');
                $selfDeclarationName = $selfDeclarationFile->getClientOriginalName();
            }

            $cbqPath = null;
            $cbqName = null;
            if ($request->hasFile('confidential_questionnaire_file')) {
                $cbqFile = $request->file('confidential_questionnaire_file');
                $cbqPath = $cbqFile->store('tenders/confidential_questionnaires', 'public');
                $cbqName = $cbqFile->getClientOriginalName();
            }

            $tender = Tender::create([
                'title'                 => $request->input('title'),
                'slug'                  => $this->generateUniqueSlug($request->input('title')),
                'tender_no'             => $request->input('tender_no'),
                'advert_file_path'          => $advertPath,
                'advert_file_name'          => $advertName,
                'self_declaration_file_path' => $selfDeclarationPath,
                'self_declaration_file_name' => $selfDeclarationName,
                'confidential_questionnaire_file_path' => $cbqPath,
                'confidential_questionnaire_file_name' => $cbqName,
                'institution_id'        => $institutionId,
                'industry_id'           => $request->input('industry_id'),
                'county_id'             => $request->input('county_id'),
                'closing_date_and_time' => $request->input('closing_date_and_time'),
                'expiry_date'           => $request->input('expiry_date'),
                'tender_status_id'      => $activeStatus?->id,
                'description'           => $request->input('description'),
                'key_requirements'      => $request->input('key_requirements'),
                'tender_link_process'   => $request->boolean('tender_link_process', false),
                'tender_fee_amount'     => $request->input('tender_fee_amount'),
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

            // Persist tender requirements if provided
            if ($request->filled('requirements')) {
                $requirements = $request->input('requirements');
                foreach ($requirements as $req) {
                    TenderRequirement::create([
                        'tender_id'  => $tender->id,
                        'title'      => $req['title'] ?? null,
                        'notes'      => $req['notes'] ?? null,
                        'mandatory'  => isset($req['mandatory']) ? (bool)$req['mandatory'] : false,
                        'source'     => $req['source'] ?? null,
                        'source_id'  => $req['source_id'] ?? null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }

            // Persist tender categories if provided
            if ($request->filled('categories')) {
                foreach ($request->input('categories') as $idx => $cat) {
                    TenderCategory::create([
                        'tender_id' => $tender->id,
                        'tender_no' => $cat['tender_no'] ?? '',
                        'title'     => $cat['title'] ?? '',
                        'position'  => $idx,
                    ]);
                }
            }
        });

        // Individual notifications disabled - using batch notifications at 8 AM and 4 PM instead
        // if ($tender) {
        //     SendTenderNotificationJob::dispatch($tender);
        // }

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
        $tender = Tender::with(['files', 'requirements', 'categories', 'institution.institutionType'])->findOrFail($id);

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
            'commonRequirements' => CommonRequirement::select(['id', 'title', 'notes', 'mandatory'])->orderBy('title')->get(),
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

            $tenderUpdate = [
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
                'tender_link_process'   => $request->boolean('tender_link_process', false),
                'tender_fee_amount'     => $request->input('tender_fee_amount'),
                'updated_by'            => $userId,
            ];

            // Advert: replace, remove, or leave alone.
            if ($request->hasFile('advert_file')) {
                if ($tender->advert_file_path) {
                    Storage::disk('public')->delete($tender->advert_file_path);
                }
                $advertFile = $request->file('advert_file');
                $tenderUpdate['advert_file_path'] = $advertFile->store('tenders/adverts', 'public');
                $tenderUpdate['advert_file_name'] = $advertFile->getClientOriginalName();
            } elseif ($request->boolean('remove_advert') && $tender->advert_file_path) {
                Storage::disk('public')->delete($tender->advert_file_path);
                $tenderUpdate['advert_file_path'] = null;
                $tenderUpdate['advert_file_name'] = null;
            }

            // Self declaration: replace, remove, or leave alone.
            if ($request->hasFile('self_declaration_file')) {
                if ($tender->self_declaration_file_path) {
                    Storage::disk('public')->delete($tender->self_declaration_file_path);
                }
                $sdFile = $request->file('self_declaration_file');
                $tenderUpdate['self_declaration_file_path'] = $sdFile->store('tenders/self_declarations', 'public');
                $tenderUpdate['self_declaration_file_name'] = $sdFile->getClientOriginalName();
            } elseif ($request->boolean('remove_self_declaration') && $tender->self_declaration_file_path) {
                Storage::disk('public')->delete($tender->self_declaration_file_path);
                $tenderUpdate['self_declaration_file_path'] = null;
                $tenderUpdate['self_declaration_file_name'] = null;
            }

            // Confidential Business Questionnaire template: replace, remove, or leave alone.
            if ($request->hasFile('confidential_questionnaire_file')) {
                if ($tender->confidential_questionnaire_file_path) {
                    Storage::disk('public')->delete($tender->confidential_questionnaire_file_path);
                }
                $cbqFile = $request->file('confidential_questionnaire_file');
                $tenderUpdate['confidential_questionnaire_file_path'] = $cbqFile->store('tenders/confidential_questionnaires', 'public');
                $tenderUpdate['confidential_questionnaire_file_name'] = $cbqFile->getClientOriginalName();
            } elseif ($request->boolean('remove_confidential_questionnaire') && $tender->confidential_questionnaire_file_path) {
                Storage::disk('public')->delete($tender->confidential_questionnaire_file_path);
                $tenderUpdate['confidential_questionnaire_file_path'] = null;
                $tenderUpdate['confidential_questionnaire_file_name'] = null;
            }

            $tender->update($tenderUpdate);

            // Sync tender requirements
            if ($request->filled('requirements')) {
                // Remove existing entries then re-insert
                TenderRequirement::where('tender_id', $tender->id)->delete();
                $requirements = $request->input('requirements');
                foreach ($requirements as $req) {
                    TenderRequirement::create([
                        'tender_id'  => $tender->id,
                        'title'      => $req['title'] ?? null,
                        'notes'      => $req['notes'] ?? null,
                        'mandatory'  => isset($req['mandatory']) ? (bool)$req['mandatory'] : false,
                        'source'     => $req['source'] ?? null,
                        'source_id'  => $req['source_id'] ?? null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }

            // Sync tender categories: preserve existing IDs so applications keep their links
            $incomingCategories = $request->input('categories', []);
            $keepIds = collect($incomingCategories)
                ->pluck('id')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->all();

            TenderCategory::where('tender_id', $tender->id)
                ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();

            foreach ($incomingCategories as $idx => $cat) {
                $payload = [
                    'tender_id' => $tender->id,
                    'tender_no' => $cat['tender_no'] ?? '',
                    'title'     => $cat['title'] ?? '',
                    'position'  => $idx,
                ];

                if (! empty($cat['id'])) {
                    TenderCategory::where('id', $cat['id'])
                        ->where('tender_id', $tender->id)
                        ->update($payload);
                } else {
                    TenderCategory::create($payload);
                }
            }

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
