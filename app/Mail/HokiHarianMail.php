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
        $tgl = \Illuminate\Support\Carbon::now(\App\Support\HariWib::ZONA)->locale('id')->isoFormat('D MMMM Y'); // tanggal WIB, bukan UTC
        return new Envelope(
            subject: "✦ Peta Hoki Harian Anda — {$tgl}",
            using: [fn (\Symfony\Component\Mime\Email $m) => \App\Support\EmailLogger::tandai($m, 'hoki_harian')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.hoki-harian');
    }
}
