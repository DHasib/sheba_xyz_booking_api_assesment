<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Booking;

class BookingStatusChanged extends Mailable
{
    use Queueable, SerializesModels;



     public Booking $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
            // dd($this->booking);
        $status = ucfirst($this->booking->status);

        return new Envelope(
            subject: "Your booking has been {$status}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // dd($this->booking);
       return new Content(
            view: 'emails.booking.status', // your Blade template
            with: [
                'booking' => $this->booking,
                'status'  => ucfirst($this->booking->status),
            ],
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
