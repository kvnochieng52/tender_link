<?php

namespace App\Mail;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  User                                    $user
     * @param  Tender                                  $tender
     * @param  array<int, \App\Models\Application>     $applications  Every row created for this submission.
     */
    public function __construct(
        public User $user,
        public Tender $tender,
        public array $applications,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your tender application has been received - ' . $this->tender->title,
        );
    }

    public function content(): Content
    {
        // Summary bits used by the Blade template.
        $first     = $this->applications[0] ?? null;
        $categories = collect($this->applications)
            ->map(fn ($a) => $a->tenderCategory)
            ->filter()
            ->values()
            ->all();

        $files = $first
            ? $first->files()->get(['file_name', 'filepath'])->all()
            : [];

        return new Content(
            view: 'emails.application-submitted',
            with: [
                'user'               => $this->user,
                'tender'             => $this->tender,
                'applications'       => $this->applications,
                'primary'            => $first,
                'categories'         => $categories,
                'requirementFiles'   => $files,
                'submittedAt'        => $first?->created_at,
                'canAmendUntil'      => $this->tender->closing_date_and_time,
            ],
        );
    }
}
