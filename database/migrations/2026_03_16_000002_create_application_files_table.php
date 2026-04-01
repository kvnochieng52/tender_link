<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_requirement_id')->nullable()->constrained('tender_requirements')->nullOnDelete();
            $table->string('file_name');
            $table->string('filepath');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_files');
    }
};
