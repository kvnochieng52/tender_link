<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable()->after('is_active');
        });

        Schema::table('tender_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });

        Schema::table('tender_payments', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};
