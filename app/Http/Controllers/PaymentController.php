<?php

namespace App\Http\Controllers;

use App\Data\FiturData;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // GET /payment/{featureId} — halaman instruksi transfer / status verifikasi
    public function konfirmasi(int $featureId)
    {
        $fitur = FiturData::cari($featureId);
        if (!$fitur) abort(404);

        $user = $this->userDariSession();
        if (!$user) {
            return redirect()->route('trial.index')
                ->with('info', 'Silakan isi data lahir Anda terlebih dahulu.');
        }

        // Sudah punya akses? Langsung ke form
        if ($user->hasAccessToFeature($featureId)) {
            return redirect()->route('laporan.form', $featureId);
        }

        $order = Order::where('user_id', $user->id)
            ->where('feature_id', $featureId)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $rekening = config('payment.bank_accounts');

        return view('payment.konfirmasi', compact('user', 'fitur', 'featureId', 'order', 'rekening'));
    }

    // POST /payment/{featureId} — catat pengajuan transfer manual, menunggu verifikasi admin
    public function proses(int $featureId, Request $request)
    {
        $fitur = FiturData::cari($featureId);
        if (!$fitur) abort(404);

        $user = $this->userDariSession();
        if (!$user) abort(403);

        $request->validate([
            'bank' => 'required|in:' . collect(config('payment.bank_accounts'))->pluck('kode')->implode(','),
        ]);

        // Idempotency: reuse order pending yang sudah ada, jangan buat kode unik baru tiap submit
        $order = Order::where('user_id', $user->id)
            ->where('feature_id', $featureId)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$order) {
            $amount     = $fitur['harga'];
            $uniqueCode = $this->generateUniqueCode($amount);

            $order = Order::create([
                'user_id'          => $user->id,
                'feature_id'       => $featureId,
                'amount'           => $amount,
                'unique_code'      => $uniqueCode,
                'transfer_amount'  => $amount + $uniqueCode,
                'bank_tujuan'      => $request->bank,
                'payment_method'   => 'manual_transfer',
                'gateway_order_id' => 'MANUAL-' . strtoupper(Str::random(10)),
                'status'           => 'pending',
            ]);
        }

        return redirect()->route('payment.konfirmasi', $featureId)
            ->with('success', 'Terima kasih! Transfer Anda akan kami verifikasi secepatnya (maks. 1x24 jam).');
    }

    /**
     * Kode unik 3 digit ditambahkan ke nominal supaya tiap transfer bisa dicocokkan
     * satu-satu dengan mutasi rekening. Dicek ulang agar tidak ada dua order pending
     * dengan harga dasar sama yang menghasilkan total transfer sama persis.
     */
    private function generateUniqueCode(int $amount): int
    {
        do {
            $code = random_int(1, 999);
        } while (
            Order::where('status', 'pending')
                ->where('amount', $amount)
                ->where('unique_code', $code)
                ->exists()
        );

        return $code;
    }

    private function userDariSession(): ?User
    {
        $userId = Session::get('user_id');
        return $userId ? User::find($userId) : null;
    }
}
