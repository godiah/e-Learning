<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $courseApproval;
    public $status;

    /**
     * Create a new message instance.
     *
     * @param  $courseApproval
     * @param  $status
     */
    public function __construct($courseApproval, $status)
    {
        $this->courseApproval = $courseApproval;
        $this->status = $status;
    }

    public function build()
    {
        $subject = $this->status === 'approved' 
            ? 'Your Course has been Approved!' 
            : 'Your Course Application was Rejected';

        return $this->subject($subject)
                    ->view('emails.course-approval')
                    ->with([
                        'courseTitle' => $this->courseApproval->title,
                        'status' => $this->status,
                    ]);
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
