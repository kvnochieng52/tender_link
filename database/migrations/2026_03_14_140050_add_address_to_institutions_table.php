<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('institutions', 'address')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table) {
            $table->string('address')->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('institutions', 'address')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
