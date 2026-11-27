<?php

namespace App\Mail;

use App\Models\Consulta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificación al correo institucional cuando llega una consulta (RF-15).
 */
class NuevaConsultaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Consulta $consulta) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->consulta->correo, $this->consulta->nombre)],
            subject: 'Nueva consulta: '.($this->consulta->categoria?->nombre ?? 'General'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.nueva-consulta',
            with: [
                // Ruta de la SPA del panel (/admin/:uri/:id/editar).
                'enlacePanel' => url('/admin/consultas/'.$this->consulta->id.'/editar'),
            ],
        );
    }
}
