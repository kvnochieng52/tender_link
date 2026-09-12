<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['compliance', 'technical', 'financial']);
            $table->string('title');
            $table->text('notes')->nullable();
            $table->decimal('max_score', 8, 2)->default(100);
            $table->decimal('weight', 8, 2)->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['tender_id', 'category', 'position']);
        });

        Schema::create('application_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('evaluation_criteria')->cascadeOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['application_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_evaluation_scores');
        Schema::dropIfExists('evaluation_criteria');
    }
};
