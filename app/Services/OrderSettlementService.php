<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat order dinyatakan lunas — dipakai konfirmasi admin (transfer manual)
 * dan webhook SumoPod (QRIS). Idempoten: memanggil dua kali tidak menggandakan akses/komisi.
 */
class OrderSettlementService
{
    /** @return bool true bila order baru saja diubah menjadi lunas, false bila sudah bukan pending. */
    public function settle(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
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
        if (! $user || ! $user->referred_by_affiliate_id) return;

        $affiliate = $user->affiliate;
        if (! $affiliate || ! $affiliate->isActive()) return;

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
