<?php

namespace App\Mail;

use App\Models\Compra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;

class BitlletComprat extends Mailable
{
    use Queueable, SerializesModels;

    public $compra;

    /**
     * Create a new message instance.
     */
    public function __construct(Compra $compra)
    {
        $this->compra = $compra->load(['bitllets', 'volIntern.modelAvio']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'El teu bitllet de last24bcn - Reserva #' . $this->compra->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bitllet',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $vol = $this->compra->volIntern;
        $bitllets = $this->compra->bitllets;

        // Generar QR codes per al PDF (mateixa lògica que al controlador)
        $qrCodes = [];
        foreach ($bitllets as $b) {
            $contingutQr = 'last24bcn-R' . $this->compra->id
                . '-' . $vol->origenIata . $vol->destiIata
                . '-S' . $b->fila . chr(64 + $b->columna)
                . '-' . $b->nomPassatger;

            $opcions = new QROptions([
                'outputInterface' => QRMarkupSVG::class,
            ]);
            $qr = new QRCode($opcions);
            $qrCodes[] = $qr->render($contingutQr);
        }

        // Generar el PDF en memòria
        $pdf = Pdf::loadView('bitllet-pdf', [
            'compra' => $this->compra,
            'vol' => $vol,
            'bitllets' => $bitllets,
            'qrCodes' => $qrCodes,
        ]);
        $pdf->setPaper('A4', 'portrait');

        return [
            Attachment::fromData(fn () => $pdf->output(), 'last24bcn-Reserva-' . $this->compra->id . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
