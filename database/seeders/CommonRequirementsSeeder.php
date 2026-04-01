<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommonRequirement;

class CommonRequirementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => 'Valid Certificate of Incorporation / Registration',
                'notes' => 'Include change of particulars where applicable.',
                'mandatory' => true,
            ],
            [
                'title' => 'Valid Tax Compliance Certificate',
                'notes' => 'Certificate must be valid at submission date.',
                'mandatory' => true,
            ],
            [
                'title' => 'CR12 from Registrar of Companies',
                'notes' => 'Issued within the last six (6) months.',
                'mandatory' => true,
            ],
            [
                'title' => 'Signed & Stamped Form of Tender',
                'notes' => 'Use company letterhead.',
                'mandatory' => true,
            ],
            [
                'title' => 'Bid Security / Guarantee',
                'notes' => 'Set amount and validity period in days.',
                'mandatory' => true,
            ],
            [
                'title' => 'Valid Business Permit',
                'notes' => 'Issued by county government for current year.',
                'mandatory' => true,
            ],
            [
                'title' => 'Certified Audited Accounts',
                'notes' => 'Attach required years and CPA/ICPAK details.',
                'mandatory' => true,
            ],
            [
                'title' => 'Signed Confidential Business Questionnaire',
                'notes' => 'Indicate physical, postal, telephone and email contacts.',
                'mandatory' => true,
            ],
            [
                'title' => 'Certificate of Independent Tender Determination',
                'notes' => 'Signed and stamped in company letterhead.',
                'mandatory' => true,
            ],
        ];

        foreach ($items as $item) {
            CommonRequirement::updateOrCreate(
                ['title' => $item['title']],
                ['notes' => $item['notes'], 'mandatory' => $item['mandatory']]
            );
        }
    }
}
