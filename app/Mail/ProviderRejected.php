<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $retryUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre inscription n\'a pas été approuvée - AjiKhdam',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider-rejected',
        );
    }
}
