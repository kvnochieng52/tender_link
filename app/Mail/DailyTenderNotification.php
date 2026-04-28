<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class DailyTenderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $tenders;
    public $user;
    public $timeOfDay;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $tenders, User $user)
    {
        $this->tenders = $tenders;
        $this->user = $user;

        // Determine if this is morning or evening notification
        $hour = Carbon::now()->hour;
        $this->timeOfDay = ($hour < 12) ? 'morning' : 'evening';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $count = $this->tenders->count();
        $subject = $count === 1
            ? 'New Tender Available - ' . Carbon::now()->format('F j, Y')
            : $count . ' New Tenders Available - ' . Carbon::now()->format('F j, Y');

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-tender-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}