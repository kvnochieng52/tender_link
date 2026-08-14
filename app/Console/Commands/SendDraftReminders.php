<?php

namespace App\Console\Commands;

use App\Mail\DraftReminderMail;
use App\Models\ApplicationDraft;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDraftReminders extends Command
{
    protected $signature = 'drafts:remind
        {--stale-days=2 : Only remind on drafts untouched for at least this many days}
        {--within-days=5 : Only remind if the tender closes within this many days}
        {--cooldown-days=3 : Do not re-send within this many days of the last reminder}';

    protected $description = 'Email tenderers a reminder about their pending draft applications when the deadline is approaching.';

    public function handle(): int
    {
        $staleDays    = (int) $this->option('stale-days');
        $withinDays   = (int) $this->option('within-days');
        $cooldownDays = (int) $this->option('cooldown-days');

        $now        = Carbon::now();
        $staleBefore = $now->copy()->subDays($staleDays);
        $deadlineBefore = $now->copy()->addDays($withinDays);
        $cooldownCutoff = $now->copy()->subDays($cooldownDays);

        $drafts = ApplicationDraft::query()
            ->with(['user', 'tender'])
            ->whereHas('tender', function ($q) use ($now, $deadlineBefore) {
                $q->whereNotNull('closing_date_and_time')
                    ->where('closing_date_and_time', '>', $now)
                    ->where('closing_date_and_time', '<=', $deadlineBefore);
            })
            ->where('updated_at', '<', $staleBefore)
            ->where(function ($q) use ($cooldownCutoff) {
                $q->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<', $cooldownCutoff);
            })
            ->get();

        $sent = 0;
        foreach ($drafts as $draft) {
            $user   = $draft->user;
            $tender = $draft->tender;

            if (! $user || ! $user->email || ! $tender) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new DraftReminderMail($user, $tender, $draft));
                $draft->last_reminder_sent_at = $now;
                $draft->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Failed sending draft reminder (draft #' . $draft->id . '): ' . $e->getMessage());
            }
        }

        $this->info(sprintf('Draft reminders sent: %d / %d eligible.', $sent, $drafts->count()));

        return self::SUCCESS;
    }
}
