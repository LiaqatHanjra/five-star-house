<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public string $audience)
    {
    }

    public function envelope(): Envelope
    {
        $subject = $this->audience === 'owner'
            ? 'New booking '.$this->booking->reference
            : 'Your Five Star House booking is confirmed';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.booking-confirmed');
    }
}
