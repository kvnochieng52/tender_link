<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('#6c757d');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('application_statuses')->insert([
            ['name' => 'Pending',    'color' => '#6c757d', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shortlisted', 'color' => '#0d6efd', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rejected',   'color' => '#dc3545', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Confirmed',  'color' => '#198754', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('application_statuses');
    }
};
