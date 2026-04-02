<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->foreignId('application_process_status_id')
                ->nullable()
                ->after('tender_status_id')
                ->constrained('application_process_statuses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['application_process_status_id']);
            $table->dropColumn('application_process_status_id');
        });
    }
};
