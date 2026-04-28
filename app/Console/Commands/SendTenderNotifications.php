<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyTenderNotificationsJob;
use Illuminate\Console\Command;

class SendTenderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenders:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send tender notification emails to all subscribed users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending tender notifications...');

        SendDailyTenderNotificationsJob::dispatch();

        $this->info('Tender notification job has been dispatched to the queue.');

        return Command::SUCCESS;
    }
}