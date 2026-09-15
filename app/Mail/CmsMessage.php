<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CmsMessage extends Mailable
{
    /** @param  array<string, mixed>  $data */
    public function __construct(
        public string $mailSubject,
        public string $viewName,
        public array $data = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(view: $this->viewName, with: $this->data);
    }
}
