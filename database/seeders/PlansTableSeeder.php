<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $plans = [
            [
                'plan_name' => 'Bronze',
                'period' => 7,
                'amount' => 250,
                'description' => 'Bronze - 1 week access',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'plan_name' => 'Silver',
                'period' => 31,
                'amount' => 1000,
                'description' => 'Silver - Monthly',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'plan_name' => 'Pro',
                'period' => 90,
                'amount' => 4500,
                'description' => 'Pro - 3 months',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'plan_name' => 'Gold',
                'period' => 180,
                'amount' => 9000,
                'description' => 'Gold - 6 months',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'plan_name' => 'King',
                'period' => 365,
                'amount' => 15000,
                'description' => 'King - 12 months',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('plans')->insert($plans);
    }
}
