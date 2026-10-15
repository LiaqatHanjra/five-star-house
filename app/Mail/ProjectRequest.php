<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public string $audience)
    {
    }

    public function envelope(): Envelope
    {
        $subject = $this->audience === 'owner'
            ? 'New project request from '.$this->booking->full_name
            : 'We received your Five Star House project';

        $replyTo = $this->audience === 'owner'
            ? new Address($this->booking->email, $this->booking->full_name)
            : new Address(config('mail.from.address', 'info@fivestarh.com'), config('mail.from.name', 'Five Star House'));

        return new Envelope(
            subject: $subject,
            replyTo: [$replyTo],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.project-request');
    }
}
