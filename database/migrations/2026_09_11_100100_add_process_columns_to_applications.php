<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('due_diligence_status', ['pending', 'in_progress', 'completed', 'failed'])
                ->nullable()
                ->after('disclaimer_accepted_at');
            $table->text('due_diligence_notes')->nullable()->after('due_diligence_status');
            $table->timestamp('due_diligence_completed_at')->nullable()->after('due_diligence_notes');

            $table->timestamp('recommended_at')->nullable()->after('due_diligence_completed_at');
            $table->text('recommendation_note')->nullable()->after('recommended_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'due_diligence_status',
                'due_diligence_notes',
                'due_diligence_completed_at',
                'recommended_at',
                'recommendation_note',
            ]);
        });
    }
};
