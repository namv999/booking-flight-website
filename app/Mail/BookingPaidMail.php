<?php

namespace App\Mail;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingPaidMail extends Mailable
{
    use Queueable;

    private ?Booking $resolvedBooking = null;

    public function __construct(public int $bookingId) {}

    private function booking(): Booking
    {
        return $this->resolvedBooking ??= Booking::with([
            'user',
            'bookingFlights.flight.aircraft' => fn ($query) => $query->withTrashed(),
            'bookingFlights.flight.aircraft.airline' => fn ($query) => $query->withTrashed(),
            'bookingFlights.flight.departureAirport' => fn ($query) => $query->withTrashed(),
            'bookingFlights.flight.arrivalAirport' => fn ($query) => $query->withTrashed(),
            'bookingFlights.tickets.passenger',
            'bookingFlights.tickets.flightSeat.seat',
        ])->findOrFail($this->bookingId);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Xác nhận vé máy bay - Booking #'.$this->bookingId);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-paid',
            with: ['booking' => $this->booking()],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('booking-history.ticket-pdf', ['booking' => $this->booking()]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                've-booking-'.$this->bookingId.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
