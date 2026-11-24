<?php

namespace App\Mail;

use App\Models\Cita;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmacionCita extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Cita $cita) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirmación de cita de donación — DonaVida');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirmacion-cita');
    }
}
