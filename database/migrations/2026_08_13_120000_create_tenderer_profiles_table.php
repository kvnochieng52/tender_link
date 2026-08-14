<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenderer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('business_name')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('business_type')->nullable();
            $table->unsignedSmallInteger('year_of_registration')->nullable();

            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('county_id')->nullable()->constrained('counties')->nullOnDelete();

            $table->text('physical_address')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('website')->nullable();
            $table->text('business_description')->nullable();

            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_position')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenderer_profiles');
    }
};
