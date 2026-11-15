<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderSettlementService;
use App\Services\SumopodPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * POST /webhook/sumopod — notifikasi pembayaran dari SumoPod Managed Payment.
 * Wajib membalas 2xx < 10 detik; event ganda aman (settlement idempoten).
 */
class SumopodWebhookController extends Controller
{
    /** Project SumoPod ini dipakai bersama Assessly; order berawalan AST- diteruskan ke sana. */
    private const ASSESSLY_WEBHOOK_URL = 'https://assessly.biz.id/webhook/sumopod';

    public function handle(Request $request, SumopodPayService $sumopod, OrderSettlementService $settlement)
    {
        $raw = $request->getContent(); // body mentah — jangan diformat ulang sebelum verifikasi

        if (! $sumopod->verifikasiWebhook(
            $raw,
            $request->header('svix-id'),
            $request->header('svix-timestamp'),
            $request->header('svix-signature'),
        )) {
            Log::warning('SumoPod webhook: signature tidak valid', ['ip' => $request->ip()]);
            \App\Support\LayananStatus::gagal('webhook', 'Signature tidak valid dari ' . $request->ip());
            return response('Invalid signature', 401);
        }

        $event = $request->json('event_type');
        \App\Support\LayananStatus::ok('webhook', (string) $event);
        $data  = (array) $request->json('data', []);

        if ($event === 'payment.test') {
            return response()->json(['ok' => true]);
        }

        // Event milik Assessly (project lain di akun SumoPod yang sama) → teruskan apa adanya.
        if (str_starts_with((string) ($data['order_id'] ?? ''), 'AST-')) {
            return $this->teruskanKeAssessly($request, $raw);
        }

        $order = Order::where('payment_method', 'qris')
            ->where(function ($q) use ($data) {
                $q->where('payment_id', $data['payment_id'] ?? '__none__')
                  ->orWhere('gateway_order_id', $data['order_id'] ?? '__none__');
            })
            ->first();

        if (! $order) {
            // 200 supaya SumoPod tidak mengulang terus; tercatat di log untuk ditelusuri.
            Log::warning('SumoPod webhook: order tidak ditemukan', ['event' => $event, 'order_id' => $data['order_id'] ?? null]);
            return response()->json(['ok' => true, 'note' => 'order tidak ditemukan']);
        }

        switch ($event) {
            case 'payment.completed':
                // Nominal harus cocok dengan order — cegah settle dengan jumlah yang salah.
                if ((int) ($data['amount'] ?? -1) !== (int) $order->amount) {
                    \App\Support\LayananStatus::gagal('webhook', "Nominal tidak cocok order #{$order->id}");
                    Log::error('SumoPod webhook: nominal tidak cocok', [
                        'order' => $order->id, 'diharapkan' => $order->amount, 'diterima' => $data['amount'] ?? null,
                    ]);
                    break;
                }
                $settlement->settle($order);
                break;

            case 'payment.failed':
                if ($order->status === 'pending') $order->update(['status' => 'cancel']);
                break;

            case 'payment.expired':
                if ($order->status === 'pending') $order->update(['status' => 'expire']);
                break;
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Teruskan webhook ke Assessly dengan body MENTAH + header svix-* tanpa diubah,
     * agar Assessly bisa memverifikasi signature dengan secret yang sama.
     * Gagal → 502 supaya SumoPod mengirim ulang (pelunasan di Assessly idempoten).
     */
    private function teruskanKeAssessly(Request $request, string $raw)
    {
        try {
            $res = Http::timeout(8) // di bawah batas 10 detik SumoPod
                ->withBody($raw, 'application/json')
                ->withHeaders([
                    'svix-id'        => (string) $request->header('svix-id'),
                    'svix-timestamp' => (string) $request->header('svix-timestamp'),
                    'svix-signature' => (string) $request->header('svix-signature'),
                ])
                ->post(self::ASSESSLY_WEBHOOK_URL);

            if ($res->successful()) {
                return response()->json(['ok' => true, 'note' => 'diteruskan ke assessly']);
            }

            Log::error('Forward webhook ke Assessly gagal', ['status' => $res->status()]);
        } catch (\Throwable $e) {
            Log::error('Forward webhook ke Assessly error', ['error' => $e->getMessage()]);
        }

        return response('Forward failed', 502);
    }
}
