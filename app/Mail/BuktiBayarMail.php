<?php

namespace App\Mail;

use App\Data\FiturData;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bukti pembayaran otomatis, dikirim sekali saat order menjadi lunas (settlement).
 * Bukan faktur pajak.
 */
class BuktiBayarMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bukti Pembayaran Rumus Langit — ' . $this->order->gateway_order_id,
            using: [fn (\Symfony\Component\Mime\Email $m) => \App\Support\EmailLogger::tandai($m, 'bukti_bayar')],
        );
    }

    public function content(): Content
    {
        $order = $this->order;
        $user  = $order->user;

        $langganan = $order->feature_id < 0;
        $fitur     = $langganan ? null : FiturData::cari((int) $order->feature_id, 'id');

        $produk = $langganan
            ? ($order->feature_id === Order::SUBSCRIPTION_YEARLY_FEATURE_ID ? 'Langganan Tahunan' : 'Langganan Bulanan')
            : ($fitur['nama'] ?? 'Laporan Analisis');

        $aktifSampai = $langganan && $user
            ? optional($user->subscriptions()->latest('id')->first())->ends_at
            : null;

        $link = $langganan ? route('trial.katalog') : route('laporan.form', (int) $order->feature_id);

        return new Content(
            view: 'emails.bukti-bayar',
            text: 'emails.bukti-bayar-text',
            with: [
                'order'       => $order,
                'nama'        => $user?->name ?? '-',
                'email'       => $user?->email ?? '-',
                'produk'      => $produk,
                'langganan'   => $langganan,
                'aktifSampai' => $aktifSampai,
                'waktuBayar'  => ($order->settled_at ?? now())->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                'metode'      => $order->payment_method === 'qris' ? 'QRIS' : 'Transfer',
                'link'        => $link,
                'loginUrl'    => route('auth.login.form'),
            ],
        );
    }
}
