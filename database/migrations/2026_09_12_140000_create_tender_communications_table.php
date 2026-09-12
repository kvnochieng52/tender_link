<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40);
            $table->string('subject');
            $table->text('body');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'created_at']);
            $table->index(['tender_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_communications');
    }
};
