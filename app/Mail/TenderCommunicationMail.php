<?php

namespace App\Mail;

use App\Models\TenderCommunication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenderCommunicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TenderCommunication $communication)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->communication->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tender-communication',
            with: [
                'communication' => $this->communication,
                'tender'        => $this->communication->tender,
                'application'   => $this->communication->application,
                'recipientName' => $this->communication->recipient_name,
                'body'          => $this->communication->body,
                'subject'       => $this->communication->subject,
            ],
        );
    }
}
