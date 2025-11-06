<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $formData)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->formData['email'],
            subject: 'Contact Form Submission',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact', // This will be our email template
        );
    }
}