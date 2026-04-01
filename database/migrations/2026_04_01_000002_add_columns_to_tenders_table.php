<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->boolean('tender_link_process')->default(0)->comment('Enable requirements/linking process');
            $table->decimal('tender_fee_amount', 10, 2)->nullable()->comment('Fee amount for tender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['tender_link_process', 'tender_fee_amount']);
        });
    }
};
