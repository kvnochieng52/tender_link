<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            ['name' => 'Construction', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Supply', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ICT', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Agro', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Health', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Energy', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Transport', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('industries')->upsert($rows, ['name'], ['active', 'updated_at']);
    }
}
