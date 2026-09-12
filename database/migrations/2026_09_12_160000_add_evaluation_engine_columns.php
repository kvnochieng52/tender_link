<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->decimal('compliance_weight', 5, 2)->default(0)->after('tender_fee_amount');
            $table->decimal('technical_weight', 5, 2)->default(70)->after('compliance_weight');
            $table->decimal('financial_weight', 5, 2)->default(30)->after('technical_weight');
            $table->timestamp('criteria_locked_at')->nullable()->after('financial_weight');
        });

        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->boolean('is_mandatory')->default(false)->after('category');
            $table->string('scoring_method', 20)->default('percentage')->after('is_mandatory');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['compliance_weight', 'technical_weight', 'financial_weight', 'criteria_locked_at']);
        });

        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropColumn(['is_mandatory', 'scoring_method']);
        });
    }
};
