<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_clarifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'created_at']);
        });

        Schema::create('tender_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('awarded_at')->nullable();
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tender_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_activity_logs');
        Schema::dropIfExists('tender_awards');
        Schema::dropIfExists('tender_clarifications');
    }
};
