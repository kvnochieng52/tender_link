<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationEvaluationScore;
use App\Models\ApplicationNote;
use App\Models\ApplicationStatus;
use App\Models\County;
use App\Models\EvaluationCriterion;
use App\Models\Industry;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tender;
use App\Models\TenderActivityLog;
use App\Models\TenderAward;
use App\Models\TenderCategory;
use App\Models\TenderClarification;
use App\Models\TenderStatus;
use App\Models\TendererProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Generates a realistic slice of workspace-ready test data:
 *   3 institutions, 3 tenders (one prequalification with categories),
 *   6 bidder users with full TendererProfiles,
 *   ~12 applications spread across tenders,
 *   evaluation criteria + partial scoring so counters, ranks, and status
 *   badges populate meaningfully,
 *   shortlist/DD/recommendation/award/clarification/notes/audit rows.
 *
 * Idempotent — safe to re-run. Everything keyed by natural identifiers
 * (institution name, tender_no, user email) and upserted.
 */
class SampleWorkspaceDataSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data — ensure the lookup tables have rows.
        $this->call([
            IndustrySeeder::class,
            CountySeeder::class,
            InstitutionTypeSeeder::class,
            TenderStatusSeeder::class,
        ]);

        // ── Institutions ─────────────────────────────────────────────
        $type = InstitutionType::first();

        $byon = Institution::updateOrCreate(
            ['institution_name' => 'BYON LTD'],
            [
                'institution_type_id' => $type?->id,
                'email'               => 'procurement@byon.co.ke',
                'telephone'           => '+254 720 111 111',
                'website'             => 'https://byon.example.ke',
                'address'             => 'Westlands, Nairobi',
            ],
        );

        $nosta = Institution::updateOrCreate(
            ['institution_name' => 'NOSTA Group'],
            [
                'institution_type_id' => $type?->id,
                'email'               => 'tenders@nosta.co.ke',
                'telephone'           => '+254 720 222 222',
                'website'             => 'https://nosta.example.ke',
                'address'             => 'Karen, Nairobi',
            ],
        );

        $kyc = Institution::updateOrCreate(
            ['institution_name' => 'KYC LTD'],
            [
                'institution_type_id' => $type?->id,
                'email'               => 'procurement@kyc.co.ke',
                'telephone'           => '+254 720 333 333',
                'website'             => 'https://kyc.example.ke',
                'address'             => 'Kilimani, Nairobi',
            ],
        );

        $industry     = Industry::first();
        $county       = County::first();
        $activeStatus = TenderStatus::where('name', 'Active')->first();

        // ── Tender 1: BYON single-item ──────────────────────────────
        $byonTender = Tender::updateOrCreate(
            ['tender_no' => 'BYON/2026/ICT-001'],
            [
                'title'                 => 'Supply and Delivery of ICT Equipment - FY 2026/27',
                'slug'                  => Str::slug('Supply and Delivery of ICT Equipment - FY 2026-27'),
                'institution_id'        => $byon->id,
                'industry_id'           => $industry?->id,
                'county_id'             => $county?->id,
                'closing_date_and_time' => now()->addDays(21),
                'expiry_date'           => now()->addDays(30),
                'tender_status_id'      => $activeStatus?->id,
                'description'           => '<p>Sample tender for workspace testing.</p>',
                'tender_link_process'   => false,
            ],
        );

        // ── Tender 2: NOSTA prequalification with 5 categories ──────
        $nostaTender = Tender::updateOrCreate(
            ['tender_no' => 'NP/2026-2029/PREQ'],
            [
                'title'                 => 'Prequalification of Suppliers for the Period FY 2026 - 2029',
                'slug'                  => Str::slug('Prequalification of Suppliers 2026 2029 NOSTA'),
                'institution_id'        => $nosta->id,
                'industry_id'           => $industry?->id,
                'county_id'             => $county?->id,
                'closing_date_and_time' => now()->addDays(14),
                'expiry_date'           => now()->addYears(3),
                'tender_status_id'      => $activeStatus?->id,
                'description'           => '<p>Prequalification for multiple supply categories over three financial years.</p>',
                'tender_link_process'   => false,
            ],
        );

        $nostaCategories = [
            ['NP/2026-2029/01', 'Supply of milk and/or dairy products (Fresh Pasteurized Milk, Yoghurt, Ice cream, Butter, Cheese)'],
            ['NP/2026-2029/02', 'Supply and delivery of meat and meat products (Cubed or diced, minced meat, sausages)'],
            ['NP/2026-2029/03', 'Supply of general office stationery'],
            ['NP/2026-2029/04', 'Supply of cleaning products and consumables'],
            ['NP/2026-2029/05', 'Provision of security guarding services'],
        ];
        foreach ($nostaCategories as $idx => [$no, $title]) {
            TenderCategory::updateOrCreate(
                ['tender_id' => $nostaTender->id, 'tender_no' => $no],
                ['title' => $title, 'position' => $idx],
            );
        }

        // ── Tender 3: KYC insurance ─────────────────────────────────
        $kycTender = Tender::updateOrCreate(
            ['tender_no' => 'KYC/2026/INS-004'],
            [
                'title'                 => 'Provision of Corporate Insurance Services - 2026',
                'slug'                  => Str::slug('Provision of Corporate Insurance Services 2026 KYC'),
                'institution_id'        => $kyc->id,
                'industry_id'           => $industry?->id,
                'county_id'             => $county?->id,
                'closing_date_and_time' => now()->addDays(7),
                'expiry_date'           => now()->addDays(45),
                'tender_status_id'      => $activeStatus?->id,
                'description'           => '<p>Comprehensive corporate insurance cover for 2026.</p>',
                'tender_link_process'   => false,
            ],
        );

        // ── Evaluation criteria — same 3-section shape on all 3 tenders ─
        $criteriaSet = [
            ['compliance', 'Valid business registration (CR12/CR2)', 100, 1],
            ['compliance', 'Valid KRA Tax Compliance Certificate',   100, 1],
            ['compliance', 'CBQ form fully signed and stamped',      100, 1],
            ['technical',  'Experience with similar contracts',      100, 3],
            ['technical',  'Technical capacity & staff qualifications', 100, 2],
            ['technical',  'Quality assurance / certifications',      100, 1],
            ['financial',  'Bid price competitiveness',               100, 3],
            ['financial',  'Financial capacity (audited accounts)',    100, 2],
        ];

        foreach ([$byonTender, $nostaTender, $kycTender] as $t) {
            foreach ($criteriaSet as $idx => [$cat, $title, $max, $weight]) {
                EvaluationCriterion::updateOrCreate(
                    ['tender_id' => $t->id, 'category' => $cat, 'title' => $title],
                    ['max_score' => $max, 'weight' => $weight, 'position' => $idx],
                );
            }
        }

        // ── Bidders (users + full TendererProfile) ───────────────────
        $bidderData = [
            ['Acme Supplies Ltd',        'bidder1@example.com', 'Acme Supplies Ltd',        'PVT/2018/00121', 'P051567890A',  12, 45_000_000, 'ISO 9001:2015, KEBS Diamond Mark'],
            ['Bright Traders EA',        'bidder2@example.com', 'Bright Traders EA',        'PVT/2020/00445', 'P051987654B',  6,  18_500_000, 'ISO 9001:2015'],
            ['Serengeti Distributors',   'bidder3@example.com', 'Serengeti Distributors',   'PVT/2015/00089', 'P051112233C',  15, 82_000_000, 'ISO 9001:2015, ISO 14001, KEBS Diamond Mark, NCA-3'],
            ['Nyati Logistics Kenya',    'bidder4@example.com', 'Nyati Logistics Kenya',    'PVT/2019/00332', 'P051445566D',  9,  28_000_000, 'ISO 9001:2015'],
            ['Twiga Provisioning Co.',   'bidder5@example.com', 'Twiga Provisioning Co.',   'PVT/2021/00778', 'P051778899E',  4,  9_800_000,  ''],
            ['Ubora Business Solutions', 'bidder6@example.com', 'Ubora Business Solutions', 'PVT/2016/00201', 'P051334455F',  10, 55_000_000, 'ISO 27001, ISO 9001:2015'],
        ];

        $bidders = [];
        foreach ($bidderData as [$name, $email, $company, $reg, $pin, $yrs, $turnover, $certs]) {
            $u = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'      => $name,
                    'password'  => bcrypt('password'),
                    'telephone' => '+254 700 ' . str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
                    'is_active' => true,
                ],
            );

            TendererProfile::updateOrCreate(
                ['user_id' => $u->id],
                [
                    'business_name'         => $company,
                    'registration_number'   => $reg,
                    'kra_pin'               => $pin,
                    'business_type'         => 'Private Limited Company',
                    'year_of_registration'  => 2026 - $yrs,
                    'industry_id'           => $industry?->id,
                    'county_id'             => $county?->id,
                    'physical_address'      => 'Sample Rd, Nairobi',
                    'postal_address'        => 'P.O. Box 000-00100 Nairobi',
                    'contact_person_name'   => $name . ' Director',
                    'contact_person_position'=> 'Managing Director',
                    'contact_phone'         => $u->telephone,
                    'contact_email'         => $email,
                    'years_of_experience'   => $yrs,
                    'annual_turnover'       => $turnover,
                    'certifications'        => $certs,
                ],
            );

            $bidders[] = $u;
        }

        // ── Applications ─────────────────────────────────────────────
        $pendingStatusId    = ApplicationStatus::where('name', 'Pending')->value('id');
        $shortlistedStatusId= ApplicationStatus::where('name', 'Shortlisted')->value('id');

        // Distribution:
        //   BYON tender      -> bidders 1, 2, 3      (single-item, no category)
        //   NOSTA prequal    -> bidders 1, 3, 4, 5   (spread across categories, some multi)
        //   KYC insurance    -> bidders 2, 3, 6      (single-item)
        $applicationMatrix = [
            // [tender, category_index_or_null, bidder_index, bid_amount]
            [$byonTender, null, 0, 4_250_000],
            [$byonTender, null, 1, 3_950_000],
            [$byonTender, null, 2, 4_100_000],

            [$nostaTender, 0, 0, 1_800_000],   // Acme -> dairy
            [$nostaTender, 1, 2, 2_600_000],   // Serengeti -> meat
            [$nostaTender, 2, 3, 780_000],     // Nyati -> stationery
            [$nostaTender, 2, 4, 810_000],     // Twiga -> stationery
            [$nostaTender, 3, 3, 950_000],     // Nyati -> cleaning
            [$nostaTender, 4, 5, 4_500_000],   // Ubora -> security

            [$kycTender, null, 1, 6_200_000],
            [$kycTender, null, 2, 5_950_000],
            [$kycTender, null, 5, 6_050_000],
        ];

        // Wipe any prior sample applications for these tenders so re-runs
        // stay clean (only touches our seeded tenders).
        $sampleTenderIds = [$byonTender->id, $nostaTender->id, $kycTender->id];
        Application::whereIn('tender_id', $sampleTenderIds)->delete();

        $tenderSeq = []; // per-tender running seq for application_no
        $createdApps = [];

        foreach ($applicationMatrix as $i => [$t, $catIdx, $bidderIdx, $bid]) {
            $bidder = $bidders[$bidderIdx];
            $category = $catIdx !== null
                ? TenderCategory::where('tender_id', $t->id)->orderBy('position')->skip($catIdx)->first()
                : null;

            $tenderSeq[$t->id] = ($tenderSeq[$t->id] ?? 0) + 1;
            $appNo = sprintf(
                'TP/%s/%03d-APP-%03d',
                $t->created_at?->format('Y') ?? date('Y'),
                $t->id,
                $tenderSeq[$t->id],
            );

            $app = Application::create([
                'tender_id'                 => $t->id,
                'tender_category_id'        => $category?->id,
                'application_no'            => $appNo,
                'user_id'                   => $bidder->id,
                'company_name'              => $bidder->tendererProfile->business_name,
                'telephone'                 => $bidder->telephone,
                'website'                   => 'https://example.ke',
                'county_id'                 => $county?->id,
                'address'                   => 'Sample Rd, Nairobi',
                'email'                     => $bidder->email,
                'representative_name'       => $bidder->tendererProfile->contact_person_name,
                'representative_position'   => 'Managing Director',
                'representative_telephone'  => $bidder->telephone,
                'representative_email'      => $bidder->email,
                'additional_notes'          => null,
                'bid_amount'                => $bid,
                'disclaimer_accepted_at'    => now()->subDays(mt_rand(1, 6)),
                'application_status_id'     => $pendingStatusId,
                'created_at'                => now()->subDays(mt_rand(1, 6)),
                'updated_at'                => now(),
            ]);

            $createdApps[] = $app;
        }

        // ── Scores — enter for a subset so the workspace shows real numbers ─
        $criteriaByTender = EvaluationCriterion::whereIn('tender_id', $sampleTenderIds)
            ->get()
            ->groupBy('tender_id');

        // Pre-planned score profiles (out of max_score = 100) per application id
        // (index into $createdApps). Missing entries stay unscored so the UI
        // shows Pending states too.
        $scoreProfiles = [
            // BYON — full scoring, one non-compliant
            0 => ['compliance' => [80, 90, 70], 'technical' => [70, 75, 80], 'financial' => [70, 65]],
            1 => ['compliance' => [90, 85, 90], 'technical' => [82, 80, 78], 'financial' => [85, 70]],
            2 => ['compliance' => [30, 40, 20], 'technical' => [60, 55, 45], 'financial' => [55, 50]],

            // NOSTA — mixed, some partial
            3 => ['compliance' => [95, 90, 85], 'technical' => [88, 82, 90], 'financial' => [78, 80]],
            4 => ['compliance' => [80, 85, 80], 'technical' => [75, 78, 72], 'financial' => [70, 82]],
            // 5, 6 (Twiga vs Nyati stationery) partially scored — compliance only
            5 => ['compliance' => [90, 88, 85]],
            6 => ['compliance' => [60, 55, 50]],
            // 7, 8 no scores at all (shows Pending)

            // KYC — full scoring
            9  => ['compliance' => [88, 92, 85], 'technical' => [80, 78, 82], 'financial' => [80, 85]],
            10 => ['compliance' => [90, 95, 90], 'technical' => [90, 88, 85], 'financial' => [86, 88]],
            11 => ['compliance' => [72, 70, 68], 'technical' => [65, 70, 68], 'financial' => [70, 72]],
        ];

        foreach ($scoreProfiles as $appIdx => $sections) {
            $app = $createdApps[$appIdx] ?? null;
            if (! $app) {
                continue;
            }
            $tenderCriteria = $criteriaByTender->get($app->tender_id, collect())->groupBy('category');

            foreach ($sections as $catName => $scores) {
                $catCriteria = $tenderCriteria->get($catName, collect())->values();
                foreach ($scores as $i => $val) {
                    $c = $catCriteria->get($i);
                    if (! $c) {
                        continue;
                    }
                    ApplicationEvaluationScore::updateOrCreate(
                        ['application_id' => $app->id, 'criterion_id' => $c->id],
                        ['score' => $val, 'notes' => null],
                    );
                }
            }
        }

        // ── Shortlist a couple of bidders per tender (uses status column) ─
        if ($shortlistedStatusId) {
            // BYON: shortlist top 2
            Application::whereIn('id', [$createdApps[0]->id, $createdApps[1]->id])
                ->update(['application_status_id' => $shortlistedStatusId]);
            // NOSTA: shortlist Serengeti + Ubora
            Application::whereIn('id', [$createdApps[4]->id, $createdApps[8]->id])
                ->update(['application_status_id' => $shortlistedStatusId]);
            // KYC: shortlist top 2
            Application::whereIn('id', [$createdApps[9]->id, $createdApps[10]->id])
                ->update(['application_status_id' => $shortlistedStatusId]);
        }

        // ── Due diligence: a handful of statuses ─────────────────────
        $createdApps[0]->update(['due_diligence_status' => 'completed', 'due_diligence_notes' => 'KRA & CR12 verified. Site visit passed.', 'due_diligence_completed_at' => now()->subDay()]);
        $createdApps[1]->update(['due_diligence_status' => 'in_progress', 'due_diligence_notes' => 'Awaiting bank reference letter.']);
        $createdApps[4]->update(['due_diligence_status' => 'completed', 'due_diligence_notes' => 'Cold-chain facility inspected — passes.', 'due_diligence_completed_at' => now()->subDays(2)]);
        $createdApps[9]->update(['due_diligence_status' => 'failed', 'due_diligence_notes' => 'Financial capacity below threshold.']);

        // ── Recommendations ──────────────────────────────────────────
        $createdApps[1]->update(['recommended_at' => now()->subHours(6), 'recommendation_note' => 'Best price-to-quality ratio among compliant bidders.']);
        $createdApps[4]->update(['recommended_at' => now()->subHours(3), 'recommendation_note' => 'Superior technical capacity + on-time delivery track record.']);

        // ── Award (KYC only) ─────────────────────────────────────────
        TenderAward::updateOrCreate(
            ['tender_id' => $kycTender->id],
            [
                'application_id' => $createdApps[10]->id,   // Serengeti wins KYC
                'awarded_at'     => now()->subHours(2),
                'contract_value' => 5_950_000,
                'reference_no'   => 'KYC/AWD/2026/004',
                'notes'          => 'Awarded on best evaluated bid.',
            ],
        );

        // ── Clarifications ───────────────────────────────────────────
        TenderClarification::firstOrCreate(
            ['tender_id' => $nostaTender->id, 'question' => 'Are we required to bid for all categories or can we submit interest for one?'],
            [
                'application_id' => null,
                'answer'         => 'Bidders may submit interest for one or more categories independently.',
                'answered_at'    => now()->subDay(),
            ],
        );
        TenderClarification::firstOrCreate(
            ['tender_id' => $byonTender->id, 'question' => 'What warranty period is required for the ICT equipment?'],
            [
                'application_id' => $createdApps[0]->id,
                'answer'         => null, // unanswered — visible as pending in the tab
            ],
        );

        // ── Communication notes ──────────────────────────────────────
        // Notes require an author — pick any user with the admin role, else the first user.
        $author = User::role('admin')->first() ?? User::first();
        if ($author) {
            ApplicationNote::firstOrCreate(
                ['application_id' => $createdApps[0]->id, 'note' => 'Bidder submitted addendum via email — received.'],
                ['user_id' => $author->id],
            );
            ApplicationNote::firstOrCreate(
                ['application_id' => $createdApps[4]->id, 'note' => 'Site visit completed with cold-chain team.'],
                ['user_id' => $author->id],
            );
        }

        // ── Audit trail — synthesize a few entries per tender ────────
        foreach ($sampleTenderIds as $tid) {
            TenderActivityLog::record($tid, null, 'sample.seed', 'Sample workspace data seeded.');
        }
        TenderActivityLog::record($kycTender->id, null, 'award.recorded', 'Awarded the tender to ' . $createdApps[10]->application_no . ' ' . $createdApps[10]->company_name);
        TenderActivityLog::record($nostaTender->id, null, 'shortlist.added', 'Shortlisted ' . $createdApps[4]->application_no . ' ' . $createdApps[4]->company_name);

        $this->command?->info('Sample workspace data seeded.');
        $this->command?->info('Tenders:   BYON/2026/ICT-001, NP/2026-2029/PREQ, KYC/2026/INS-004');
        $this->command?->info('Bidders:   bidder1..6@example.com  (password: "password")');
        $this->command?->info('Applications created: ' . count($createdApps));
    }
}
