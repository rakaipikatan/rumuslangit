<?php

namespace App\Mail;

use App\Models\Affiliate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateDisetujuiMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Affiliate $affiliate) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pendaftaran Afiliasi Rumus Langit Disetujui',
            using: [fn (\Symfony\Component\Mime\Email $m) => \App\Support\EmailLogger::tandai($m, 'afiliasi_disetujui')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.afiliasi-disetujui', text: 'emails.afiliasi-disetujui-text');
    }
}
