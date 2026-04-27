<?php

namespace App\Jobs;

use App\Models\Tender;
use App\Models\User;
use App\Mail\NewTenderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTenderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tender;

    /**
     * Create a new job instance.
     */
    public function __construct(Tender $tender)
    {
        $this->tender = $tender;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tender = $this->tender->load([
            'institution:id,institution_name',
            'industry:id,name',
            'county:id,name',
        ]);

        $users = User::where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();

        foreach ($users as $user) {
            Mail::to($user->email)->send(new NewTenderNotification($tender, $user));
        }
    }
}