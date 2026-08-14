<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('confidential_questionnaire_file_path')->nullable()->after('self_declaration_file_name');
            $table->string('confidential_questionnaire_file_name')->nullable()->after('confidential_questionnaire_file_path');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->string('filled_questionnaire_file_path')->nullable()->after('additional_notes');
            $table->string('filled_questionnaire_file_name')->nullable()->after('filled_questionnaire_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn([
                'confidential_questionnaire_file_path',
                'confidential_questionnaire_file_name',
            ]);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'filled_questionnaire_file_path',
                'filled_questionnaire_file_name',
            ]);
        });
    }
};
