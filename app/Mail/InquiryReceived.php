<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Permintaan Penawaran baru — '.$this->inquiry->name,
            // Replying to the notification reaches the visitor, not the
            // mailbox the notification was sent from.
            replyTo: [$this->inquiry->email],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.inquiry-received');
    }
}
