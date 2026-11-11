<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWithdrawal;
use Illuminate\Support\Facades\DB;

/**
 * Saldo afiliator + pembuatan permintaan payout otomatis.
 * Komisi "siap ditarik" = belum dibayar, sudah lewat masa tahan, belum masuk payout mana pun.
 */
class AffiliatePayoutService
{
    public static function batasTahan(): \Illuminate\Support\Carbon
    {
        return now()->subDays((int) config('payment.affiliate_hold_days', 14));
    }

    public static function minimum(): int
    {
        return (int) config('payment.affiliate_min_withdrawal', 100000);
    }

    /** @return array{total:int, ditahan:int, siap:int, diproses:int, dibayar:int} */
    public static function saldo(Affiliate $a): array
    {
        $k = $a->commissions();

        $dibayar  = (int) (clone $k)->where('status', 'paid')->sum('komisi_amount');
        $diproses = (int) (clone $k)->where('status', 'unpaid')->whereNotNull('withdrawal_id')->sum('komisi_amount');
        $siap     = (int) (clone $k)->where('status', 'unpaid')->whereNull('withdrawal_id')->where('created_at', '<=', self::batasTahan())->sum('komisi_amount');
        $ditahan  = (int) (clone $k)->where('status', 'unpaid')->whereNull('withdrawal_id')->where('created_at', '>', self::batasTahan())->sum('komisi_amount');

        return ['total' => $dibayar + $diproses + $siap + $ditahan, 'ditahan' => $ditahan, 'siap' => $siap, 'diproses' => $diproses, 'dibayar' => $dibayar];
    }

    /** Buat satu permintaan payout bila saldo siap ≥ minimum dan rekening lengkap. */
    public function buatUntuk(Affiliate $a): ?AffiliateWithdrawal
    {
        if (blank($a->bank_nama) || blank($a->bank_rekening) || blank($a->bank_atas_nama)) {
            return null;
        }

        return DB::transaction(function () use ($a) {
            $komisi = AffiliateCommission::where('affiliate_id', $a->id)
                ->where('status', 'unpaid')->whereNull('withdrawal_id')
                ->where('created_at', '<=', self::batasTahan())
                ->lockForUpdate()->get();

            $jumlah = (int) $komisi->sum('komisi_amount');
            if ($komisi->isEmpty() || $jumlah < self::minimum()) {
                return null;
            }

            $w = AffiliateWithdrawal::create([
                'affiliate_id' => $a->id, 'jumlah' => $jumlah, 'status' => 'pending',
                'bank_nama' => $a->bank_nama, 'bank_rekening' => $a->bank_rekening, 'bank_atas_nama' => $a->bank_atas_nama,
            ]);
            AffiliateCommission::whereIn('id', $komisi->pluck('id'))->update(['withdrawal_id' => $w->id]);

            return $w;
        });
    }

    /** Payout untuk semua afiliator aktif. @return int jumlah permintaan dibuat */
    public function buatSemua(): int
    {
        $n = 0;
        foreach (Affiliate::where('status', 'active')->get() as $a) {
            if ($this->buatUntuk($a)) {
                $n++;
            }
        }

        return $n;
    }

    /** Tandai satu payout sudah ditransfer: payout + komisi di dalamnya → paid. */
    public function tandaiDibayar(AffiliateWithdrawal $w): void
    {
        if ($w->status === 'paid') {
            return;
        }
        DB::transaction(function () use ($w) {
            $w->update(['status' => 'paid', 'paid_at' => now()]);
            $w->commissions()->update(['status' => 'paid', 'paid_at' => now()]);
        });
    }
}
