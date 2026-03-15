<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        $tenders = DB::table('tenders')->select(['id', 'title'])->get();

        foreach ($tenders as $tender) {
            $base = Str::slug((string) $tender->title, '-');

            if ($base === '') {
                $base = 'tender';
            }

            do {
                $candidate = $base . '-' . random_int(100000, 999999);
                $exists = DB::table('tenders')->where('slug', $candidate)->exists();
            } while ($exists);

            DB::table('tenders')
                ->where('id', $tender->id)
                ->update(['slug' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
