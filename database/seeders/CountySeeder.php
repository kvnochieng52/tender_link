<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            ['name' => 'Nairobi', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mombasa', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kisumu', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nakuru', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kiambu', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Uasin Gishu', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Machakos', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kakamega', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nyeri', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Meru', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('counties')->upsert($rows, ['name'], ['active', 'updated_at']);
    }
}
