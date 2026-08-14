<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->json('data')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_drafts');
    }
};
