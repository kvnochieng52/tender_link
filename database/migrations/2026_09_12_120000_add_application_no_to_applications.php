<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('application_no')->nullable()->unique()->after('id');
        });

        // Backfill existing rows: per-tender sequence, formatted as
        //   TP/{year_of_tender}/{tender_id:03d}-APP-{seq:03d}
        DB::table('applications')
            ->select('tender_id')
            ->distinct()
            ->pluck('tender_id')
            ->each(function ($tenderId) {
                if (! $tenderId) {
                    return;
                }
                $tender = DB::table('tenders')->where('id', $tenderId)->first(['id', 'created_at']);
                if (! $tender) {
                    return;
                }
                $year = date('Y', strtotime($tender->created_at ?? 'now'));

                $ids = DB::table('applications')
                    ->where('tender_id', $tenderId)
                    ->whereNull('application_no')
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($ids as $idx => $appId) {
                    $seq = $idx + 1;
                    $no  = sprintf('TP/%s/%03d-APP-%03d', $year, $tenderId, $seq);
                    DB::table('applications')->where('id', $appId)->update(['application_no' => $no]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['application_no']);
            $table->dropColumn('application_no');
        });
    }
};
