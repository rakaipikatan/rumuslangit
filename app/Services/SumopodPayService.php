<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien SumoPod Managed Payment (QRIS).
 * Dokumentasi: tab "Quick Start" di dashboard SumoPod → Managed Payment.
 */
class SumopodPayService
{
    /** Toleransi selisih waktu header webhook (detik) — cegah replay. */
    private const TOLERANSI_DETIK = 300;

    public function terkonfigurasi(): bool
    {
        return filled(config('services.sumopod_pay.api_key'));
    }

    /**
     * Buat payment QRIS untuk sebuah order. Mengisi payment_id, payment_link_url, payment_expires_at.
     *
     * @throws RuntimeException bila API gagal / respons tidak lengkap
     */
    public function buatPembayaran(Order $order, int $expiresInHours = 24): Order
    {
        if (! $this->terkonfigurasi()) {
            throw new RuntimeException('SumoPod Payment belum dikonfigurasi.');
        }

        $payload = [
            'order_id'                 => $order->gateway_order_id,
            'amount'                   => (int) $order->amount,
            'currency'                 => 'IDR',
            'expires_in_hours'         => min($expiresInHours, 24),
            'payment_method_type_code' => 'qris',
        ];

        // Setelah bayar, user dikembalikan ke halaman yang tahu apa yang dibeli (bukan katalog umum).
        $denganReturn = $payload + [
            'success_return_url' => route('payment.selesai', $order->gateway_order_id),
            'cancel_return_url'  => route('payment.konfirmasi', $this->slugProduk($order)),
        ];

        $kirim = fn (array $body) => Http::timeout(20)
            ->acceptJson()
            ->withHeaders(['X-Api-Key' => config('services.sumopod_pay.api_key')])
            ->post(rtrim(config('services.sumopod_pay.base_url'), '/') . '/api/v1/payments', $body);

        $response = $kirim($denganReturn);

        // Jika SumoPod menolak override return URL (validasi), ulangi tanpa itu — alamat bawaan dashboard dipakai.
        if (in_array($response->status(), [400, 422], true)) {
            \Log::warning('SumoPod menolak return URL per-payment, ulangi tanpa override', [
                'order' => $order->id, 'body' => substr($response->body(), 0, 200),
            ]);
            $response = $kirim($payload);
        }

        if ($response->failed()) {
            throw new RuntimeException('SumoPod API ' . $response->status() . ': ' . substr($response->body(), 0, 300));
        }

        $paymentId = $response->json('payment_id') ?? $response->json('data.payment_id');
        $link      = $response->json('payment_link_url') ?? $response->json('data.payment_link_url');

        if (! $paymentId || ! $link) {
            throw new RuntimeException('Respons SumoPod tidak memuat payment_id / payment_link_url.');
        }

        $expiresAt = $response->json('expires_at') ?? $response->json('data.expires_at');

        $order->update([
            'payment_id'         => $paymentId,
            'payment_link_url'   => $link,
            'payment_expires_at' => $expiresAt ? \Carbon\Carbon::parse($expiresAt) : now()->addHours($expiresInHours),
        ]);

        return $order;
    }

    /** Slug produk untuk route payment.konfirmasi (angka fitur atau literal langganan). */
    public function slugProduk(Order $order): string
    {
        return match ($order->feature_id) {
            Order::SUBSCRIPTION_MONTHLY_FEATURE_ID => 'langganan-bulanan',
            Order::SUBSCRIPTION_YEARLY_FEATURE_ID  => 'langganan-tahunan',
            default                                 => (string) $order->feature_id,
        };
    }

    /**
     * Verifikasi tanda tangan webhook (standar Svix): HMAC-SHA256 atas "{id}.{timestamp}.{rawBody}"
     * dengan secret whsec_... (base64). Header svix-signature bisa berisi beberapa "v1,<sig>".
     */
    public function verifikasiWebhook(string $rawBody, ?string $svixId, ?string $svixTimestamp, ?string $svixSignature): bool
    {
        $secret = (string) config('services.sumopod_pay.webhook_secret');

        if ($secret === '' || ! $svixId || ! $svixTimestamp || ! $svixSignature) {
            return false;
        }

        if (! ctype_digit($svixTimestamp) || abs(time() - (int) $svixTimestamp) > self::TOLERANSI_DETIK) {
            return false;
        }

        $kunci = base64_decode(str_replace('whsec_', '', $secret), true);
        if ($kunci === false || $kunci === '') {
            return false;
        }

        $diharapkan = base64_encode(hash_hmac('sha256', "{$svixId}.{$svixTimestamp}.{$rawBody}", $kunci, true));

        foreach (explode(' ', $svixSignature) as $bagian) {
            [, $sig] = array_pad(explode(',', $bagian, 2), 2, '');
            if ($sig !== '' && hash_equals($diharapkan, $sig)) {
                return true;
            }
        }

        return false;
    }
}
