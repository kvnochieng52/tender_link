<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('advert_file_path')->nullable()->after('slug');
            $table->string('advert_file_name')->nullable()->after('advert_file_path');
            $table->string('self_declaration_file_path')->nullable()->after('advert_file_name');
            $table->string('self_declaration_file_name')->nullable()->after('self_declaration_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn([
                'advert_file_path',
                'advert_file_name',
                'self_declaration_file_path',
                'self_declaration_file_name',
            ]);
        });
    }
};
