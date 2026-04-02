<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('trans_status_name')->nullable();
            $table->string('trans_status_color_code')->nullable();
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        DB::table('transaction_statuses')->insert([
            ['trans_status_name' => 'Pending',   'trans_status_color_code' => 'warning',   'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['trans_status_name' => 'Paid',       'trans_status_color_code' => 'success',   'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['trans_status_name' => 'Failed',     'trans_status_color_code' => 'danger',    'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['trans_status_name' => 'Reversed',   'trans_status_color_code' => 'info',      'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['trans_status_name' => 'Cancelled',  'trans_status_color_code' => 'secondary', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_statuses');
    }
};
