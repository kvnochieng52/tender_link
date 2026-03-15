<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstitutionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            ['name' => 'Government', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'NGO', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Private', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Parastatal', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'County Government', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'International Organization', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('institution_types')->upsert($rows, ['name'], ['active', 'updated_at']);
    }
}
