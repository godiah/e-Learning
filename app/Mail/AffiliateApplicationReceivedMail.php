<?php

namespace App\Mail;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $affiliate;

    /**
     * Create a new message instance.
     */
    public function __construct(User  $user, Affiliate $affiliate)
    {
        $this->user = $user;
        $this->affiliate = $affiliate;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Affiliate Application Received Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.affiliate_application_received',
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
