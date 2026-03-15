<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $tenders = DB::table('tenders')->select(['id', 'title', 'slug'])->get();

        foreach ($tenders as $tender) {
            $base = Str::slug((string) $tender->title, '-');

            if ($base === '') {
                $base = 'tender';
            }

            do {
                $candidate = $base . '-' . random_int(100000, 999999);
                $exists = DB::table('tenders')
                    ->where('slug', $candidate)
                    ->where('id', '!=', $tender->id)
                    ->exists();
            } while ($exists);

            DB::table('tenders')
                ->where('id', $tender->id)
                ->update(['slug' => $candidate]);
        }
    }

    public function down(): void
    {
        // No-op: slug format rollback is not required.
    }
};
