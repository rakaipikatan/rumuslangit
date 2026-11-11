<?php

namespace App\Console\Commands;

use App\Services\AffiliatePayoutService;
use Illuminate\Console\Command;

class BuatPayoutAfiliasi extends Command
{
    protected $signature = 'afiliasi:payout';

    protected $description = 'Buat permintaan payout komisi afiliator (saldo siap ≥ minimum) — dijadwalkan otomatis tiap bulan';

    public function handle(AffiliatePayoutService $svc): int
    {
        $n = $svc->buatSemua();
        $this->info("{$n} permintaan payout dibuat.");

        return self::SUCCESS;
    }
}
