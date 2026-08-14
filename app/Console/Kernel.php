<?php

namespace App\Console;

use App\Jobs\SendDailyTenderNotificationsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send tender notifications at 8:00 AM daily
        $schedule->job(new SendDailyTenderNotificationsJob())
            ->dailyAt('08:00')
            ->timezone('Africa/Nairobi')
            ->name('morning-tender-notifications')
            ->withoutOverlapping();

        // Send tender notifications at 4:00 PM daily
        $schedule->job(new SendDailyTenderNotificationsJob())
            ->dailyAt('16:00')
            ->timezone('Africa/Nairobi')
            ->name('evening-tender-notifications')
            ->withoutOverlapping();

        // Remind tenderers about drafts whose tender closes soon.
        $schedule->command('drafts:remind')
            ->dailyAt('09:00')
            ->timezone('Africa/Nairobi')
            ->name('draft-reminders')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
