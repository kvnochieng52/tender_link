<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_process_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('#6c757d'); // Bootstrap badge hex
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('application_process_statuses')->insert([
            ['name' => 'Pending',      'color' => '#6c757d', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shortlisting', 'color' => '#0d6efd', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Evaluation',   'color' => '#fd7e14', 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Closed',       'color' => '#198754', 'order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cancelled',    'color' => '#dc3545', 'order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('application_process_statuses');
    }
};
