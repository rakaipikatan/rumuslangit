<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $otp, public readonly string $jenis = 'otp') {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Verifikasi Rumus Langit: ' . $this->otp,
            using: [fn (\Symfony\Component\Mime\Email $m) => \App\Support\EmailLogger::tandai($m, $this->jenis)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            text: 'emails.otp-text', // versi teks polos: email HTML-saja lebih sering dianggap spam
        );
    }
}
