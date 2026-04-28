<?php

namespace App\Jobs;

use App\Models\Tender;
use App\Models\User;
use App\Mail\DailyTenderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendDailyTenderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all new tenders that haven't been notified yet
        $newTenders = Tender::with([
            'institution:id,institution_name',
            'industry:id,name',
            'county:id,name',
        ])
        ->where(function ($query) {
            $query->whereNull('last_notified_at')
                  ->orWhere('last_notified_at', '<', Carbon::now()->subHours(8));
        })
        ->where('status', 'active')
        ->where('closing_date_and_time', '>', Carbon::now())
        ->orderBy('created_at', 'desc')
        ->get();

        if ($newTenders->isEmpty()) {
            Log::info('No new tenders to notify about at ' . Carbon::now()->format('Y-m-d H:i:s'));
            return;
        }

        // Get all active users with verified emails
        $users = User::where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();

        $notificationCount = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new DailyTenderNotification($newTenders, $user));
                $notificationCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send daily tender notification to ' . $user->email . ': ' . $e->getMessage());
            }
        }

        // Update the last_notified_at timestamp for all notified tenders
        $tenderIds = $newTenders->pluck('id');
        Tender::whereIn('id', $tenderIds)->update(['last_notified_at' => Carbon::now()]);

        Log::info('Daily tender notification sent: ' . $newTenders->count() . ' tenders to ' . $notificationCount . ' users at ' . Carbon::now()->format('Y-m-d H:i:s'));
    }
}