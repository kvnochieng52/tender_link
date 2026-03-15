<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['Active', 'Closed', 'Cancelled'];

        foreach ($statuses as $status) {
            DB::table('tender_statuses')->updateOrInsert(
                ['name' => $status],
                ['active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
