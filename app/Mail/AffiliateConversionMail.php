<?php

namespace App\Mail;

use App\Models\Affiliate;
use App\Models\Commission;
use App\Models\ConversionTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateConversionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $affiliate;
    public $conversion;
    public $commission;

    /**
     * Create a new message instance.
     */
    public function __construct(Affiliate $affiliate, ConversionTracking $conversion, Commission $commission)
    {
        $this->affiliate = $affiliate;
        $this->conversion = $conversion;
        $this->commission = $commission;
    }

    public function build()
    {
        return $this->subject('New Conversion on Your Affiliate Link')
                    ->view('emails.affiliate_conversion');
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
