<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('closing_date_and_time');
            $table->foreignId('tender_status_id')
                ->nullable()
                ->after('expiry_date')
                ->constrained('tender_statuses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['tender_status_id']);
            $table->dropColumn(['expiry_date', 'tender_status_id']);
        });
    }
};
