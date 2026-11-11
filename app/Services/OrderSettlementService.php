<?php

namespace App\Services;

use App\Mail\BuktiBayarMail;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Satu-satunya tempat order dinyatakan lunas — dipakai konfirmasi admin (transfer manual)
 * dan webhook SumoPod (QRIS). Idempoten: memanggil dua kali tidak menggandakan akses/komisi.
 */
class OrderSettlementService
{
    /** @return bool true bila order baru saja diubah menjadi lunas, false bila sudah bukan pending. */
    public function settle(Order $order): bool
    {
        $baruLunas = DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $order || $order->status !== 'pending') {
                return false;
            }

            $order->update(['status' => 'settlement', 'settled_at' => now()]);

            $bulan = match ($order->feature_id) {
                Order::SUBSCRIPTION_MONTHLY_FEATURE_ID => 1,
                Order::SUBSCRIPTION_YEARLY_FEATURE_ID  => 12,
                default                                 => null,
            };

            if ($bulan !== null && $order->user) {
                $this->aktifkanLangganan($order->user, $bulan);
            }

            $this->catatKomisiAfiliasi($order);

            return true;
        });

        // Bukti bayar dikirim SETELAH transaksi tersimpan, lewat antrean (webhook harus membalas cepat),
        // dan kegagalan email tidak boleh membatalkan pelunasan. settle() idempoten → satu email per order.
        if ($baruLunas) {
            $this->kirimBuktiBayar($order->fresh('user'));
        }

        return $baruLunas;
    }

    /** Kirim bukti bayar ke email pelanggan (jika punya email). */
    public function kirimBuktiBayar(Order $order): void
    {
        $email = $order->user?->email;
        if (! $email) return;

        try {
            Mail::to($email)->queue(new BuktiBayarMail($order));
        } catch (\Throwable $e) {
            Log::warning('Bukti bayar gagal diantrekan', ['order' => $order->id, 'error' => $e->getMessage()]);
            \App\Support\LayananStatus::gagal('email', "Bukti bayar order #{$order->id}: " . $e->getMessage());
            \App\Support\EmailLogger::catatGagal('bukti_bayar', $email, $e->getMessage());
        }
    }

    /** Aktifkan/perpanjang langganan (perpanjang dari ends_at bila masih aktif). */
    public function aktifkanLangganan(User $user, int $bulan): void
    {
        $mulaiDari = $user->subscriptions()->where('ends_at', '>', now())->max('ends_at');

        Subscription::create([
            'user_id'    => $user->id,
            'plan'       => $bulan >= 12 ? 'yearly' : 'monthly',
            'starts_at'  => now(),
            'ends_at'    => ($mulaiDari ? \Carbon\Carbon::parse($mulaiDari) : now())->addMonths($bulan),
            'auto_renew' => false,
        ]);

        $user->update(['subscription_status' => 'active']);
    }

    /** Catat komisi afiliator (jika user direferensikan afiliator aktif). */
    public function catatKomisiAfiliasi(Order $order): void
    {
        $user = $order->user;
        if ($order->amount <= 0) return; // akses gratis dari admin tidak menghasilkan komisi
        if (! $user || ! $user->referred_by_affiliate_id) return;

        $affiliate = $user->affiliate;
        if (! $affiliate || ! $affiliate->isActive()) return;

        // Tidak ada komisi untuk pembelian oleh diri sendiri (email pelanggan = email afiliator).
        if ($affiliate->email && $user->email && strcasecmp(trim($affiliate->email), trim($user->email)) === 0) return;

        // Komisi hanya untuk pembayaran dalam N bulan sejak pembayaran PERTAMA pelanggan ini.
        $pertama = Order::where('user_id', $user->id)->where('status', 'settlement')->orderBy('settled_at')->first();
        $batas   = (int) config('payment.affiliate_commission_months', 12);
        if ($pertama && $pertama->settled_at && $order->settled_at
            && $order->settled_at->gt($pertama->settled_at->copy()->addMonths($batas))) {
            return;
        }

        AffiliateCommission::firstOrCreate(
            ['order_id' => $order->id],
            [
                'affiliate_id'  => $affiliate->id,
                'user_id'       => $user->id,
                'order_amount'  => $order->amount,
                'komisi_persen' => $affiliate->komisi_persen,
                'komisi_amount' => (int) round($order->amount * $affiliate->komisi_persen / 100),
                'status'        => 'unpaid',
            ]
        );
    }
}
