<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('application_status_id')
                ->nullable()
                ->after('additional_notes')
                ->constrained('application_statuses')
                ->nullOnDelete();

            // Rating out of 100 (e.g. 87.5)
            $table->decimal('rating', 5, 2)->nullable()->after('application_status_id');

            // Evaluator notes visible internally
            $table->text('evaluation_notes')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['application_status_id']);
            $table->dropColumn(['application_status_id', 'rating', 'evaluation_notes']);
        });
    }
};
