<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenderer_profiles', function (Blueprint $table) {
            $table->text('certifications')->nullable()->after('business_description');
            $table->unsignedSmallInteger('years_of_experience')->nullable()->after('certifications');
            $table->decimal('annual_turnover', 15, 2)->nullable()->after('years_of_experience');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('bid_amount', 15, 2)->nullable()->after('recommendation_note');
        });
    }

    public function down(): void
    {
        Schema::table('tenderer_profiles', function (Blueprint $table) {
            $table->dropColumn(['certifications', 'years_of_experience', 'annual_turnover']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('bid_amount');
        });
    }
};
