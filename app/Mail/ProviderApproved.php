<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $dashboardUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre inscription a été approuvée - AjiKhdam',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider-approved',
        );
    }
}
