<?php

namespace App\Mail;

use App\Models\ApplicationDraft;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DraftReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Tender $tender,
        public ApplicationDraft $draft,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: finish your application for ' . $this->tender->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.draft-reminder',
            with: [
                'user'         => $this->user,
                'tender'       => $this->tender,
                'draft'        => $this->draft,
                'closingDate'  => $this->tender->closing_date_and_time,
                'resumeUrl'    => route('tenders.public.show', ['slug' => $this->tender->slug]),
            ],
        );
    }
}
