<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HokiHarianMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $namaUser,
        public readonly array  $hoki,
    ) {}

    public function envelope(): Envelope
    {
        $tgl = now()->locale('id')->isoFormat('D MMMM Y');
        return new Envelope(subject: "✦ Peta Hoki Harian Anda — {$tgl}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.hoki-harian');
    }
}
