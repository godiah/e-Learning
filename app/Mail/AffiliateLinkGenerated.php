<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateLinkGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $courseName;
    public $trackingCode;
    public $shortUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $courseName, $trackingCode, $shortUrl)
    {
        $this->user = $user;
        $this->courseName = $courseName;
        $this->trackingCode = $trackingCode;
        $this->shortUrl = $shortUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Affiliate Link Generated Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.affiliate_link',
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
