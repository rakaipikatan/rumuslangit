<?php

namespace App\Http\Controllers;

use App\Data\FiturData;
use App\Models\Order;
use App\Models\User;
use App\Services\SumopodPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // GET /payment/{featureId} — halaman instruksi transfer / status verifikasi
    // $featureId juga menerima literal 'langganan-bulanan' / 'langganan-tahunan'.
    public function konfirmasi(string $featureId)
    {
        $fitur = $this->resolveFitur($featureId);
        if (!$fitur) abort(404);

        $user = $this->userDariSession();
        if (!$user) {
            return redirect()->route('trial.index')
                ->with('info', 'Silakan isi data lahir Anda terlebih dahulu.');
        }

        $subscriptionMarker = $this->subscriptionFeatureId($featureId);

        // Fitur gratis / khusus langganan tidak dijual eceran — jangan buat order Rp0.
        if ($subscriptionMarker === null && (int) $fitur['harga'] <= 0) {
            return empty($fitur['subscriber_only'])
                ? redirect()->route('laporan.form', (int) $featureId)
                : redirect()->route('trial.katalog')->with('info', 'Fitur ini khusus pelanggan langganan.');
        }

        $fitur = $this->terapkanHargaUji($fitur, $user);

        if ($subscriptionMarker !== null) {
            if ($user->isSubscriber()) {
                return redirect()->route('trial.katalog')
                    ->with('info', 'Anda sudah memiliki langganan aktif.');
            }
            $orderFeatureId = $subscriptionMarker;
        } else {
            $numericFeatureId = (int) $featureId;

            // Sudah punya akses? Langsung ke form
            if ($user->hasAccessToFeature($numericFeatureId)) {
                return redirect()->route('laporan.form', $numericFeatureId);
            }
            $orderFeatureId = $numericFeatureId;
        }

        $order = Order::where('user_id', $user->id)
            ->where('feature_id', $orderFeatureId)
            ->where('status', 'pending')
            ->where('payment_method', 'qris')
            ->latest()
            ->first();

        // Link QRIS yang sudah lewat masa berlaku dianggap expired — user bisa membuat yang baru.
        if ($order && $order->payment_method === 'qris' && $order->payment_expires_at && $order->payment_expires_at->isPast()) {
            $order->update(['status' => 'expire']);
            $order = null;
        }

        $qrisTersedia = app(SumopodPayService::class)->terkonfigurasi();

        return view('payment.konfirmasi', compact('user', 'fitur', 'featureId', 'order', 'qrisTersedia'));
    }

    // GET /payment/selesai/{gatewayOrderId} — tujuan setelah bayar QRIS.
    // Lunas → langsung ke kuesioner (atau katalog untuk langganan). Belum masuk → tunggu konfirmasi webhook.
    public function selesai(string $gatewayOrderId, Request $request)
    {
        $user = $this->userDariSession();
        if (!$user) {
            return redirect()->route('trial.index');
        }

        $order = Order::where('gateway_order_id', $gatewayOrderId)->where('user_id', $user->id)->firstOrFail();
        $slug  = app(SumopodPayService::class)->slugProduk($order);

        if ($request->expectsJson()) {
            return response()->json([
                'status'   => $order->status,
                'redirect' => $order->status === 'pending' ? null : route('payment.selesai', $gatewayOrderId),
            ]);
        }

        if ($order->isSettled()) {
            if ($order->feature_id > 0) {
                return redirect()->route('laporan.form', $order->feature_id)
                    ->with('success', __('payment.selesai_sukses_fitur'));
            }

            return redirect()->route('trial.katalog')->with('success', __('payment.selesai_sukses_langganan'));
        }

        if (in_array($order->status, ['cancel', 'expire'], true)) {
            return redirect()->route('payment.konfirmasi', $slug)->withErrors(['metode' => __('payment.selesai_gagal')]);
        }

        return view('payment.selesai', compact('order'));
    }

    // POST /payment/{featureId} — buat pembayaran QRIS (SumoPod) lalu arahkan user ke halaman bayar.
    public function proses(string $featureId, Request $request)
    {
        $fitur = $this->resolveFitur($featureId);
        if (!$fitur) abort(404);

        $user = $this->userDariSession();
        if (!$user) abort(403);

        if ($this->subscriptionFeatureId($featureId) === null && (int) $fitur['harga'] <= 0) {
            abort(422, 'Fitur ini tidak dijual eceran.');
        }

        $fitur   = $this->terapkanHargaUji($fitur, $user);
        $sumopod = app(SumopodPayService::class);

        if (! $sumopod->terkonfigurasi()) {
            return back()->withErrors(['metode' => __('payment.qris_tidak_tersedia')]);
        }

        $orderFeatureId = $this->subscriptionFeatureId($featureId) ?? (int) $featureId;
        $amount         = $fitur['harga'];

        // Idempotency: pakai ulang order QRIS pending (tak membuat payment baru tiap submit)
        $order = Order::where('user_id', $user->id)
            ->where('feature_id', $orderFeatureId)
            ->where('status', 'pending')
            ->where('payment_method', 'qris')
            ->latest()
            ->first();

        if ($order && $order->payment_expires_at && $order->payment_expires_at->isPast()) {
            $order->update(['status' => 'expire']);
            $order = null;
        }

        if (! $order) {
            $order = Order::create([
                'user_id'          => $user->id,
                'feature_id'       => $orderFeatureId,
                'amount'           => $amount,
                'transfer_amount'  => $amount,
                'payment_method'   => 'qris',
                'gateway_order_id' => 'RL-' . strtoupper(Str::random(12)),
                'status'           => 'pending',
            ]);
        }

        if (! $order->payment_link_url) {
            try {
                $sumopod->buatPembayaran($order, (int) config('payment.expire_hours', 24));
            } catch (\Throwable $e) {
                \Log::error('SumoPod: gagal membuat pembayaran', ['order' => $order->id, 'error' => $e->getMessage()]);
                \App\Support\LayananStatus::gagal('pembayaran', "Order #{$order->id}: " . $e->getMessage());
                $order->update(['status' => 'cancel']);
                return back()->withErrors(['metode' => __('payment.qris_gagal')]);
            }
        }

        \App\Support\LayananStatus::ok('pembayaran');

        return redirect()->away($order->payment_link_url);
    }

    /**
     * Harga uji sementara (PAYMENT_TEST_PRICE) khusus untuk SATU email terverifikasi (PAYMENT_TEST_EMAIL).
     * Pengguna lain tidak terpengaruh. Kosongkan kedua variabel .env setelah tes selesai.
     */
    private function terapkanHargaUji(array $fitur, User $user): array
    {
        $harga = config('payment.test_price');
        $email = config('payment.test_email');

        // Pengaman: lewat batas waktu → harga uji mati sendiri (tidak bisa tertinggal aktif di produksi)
        $sampai = config('payment.test_until');
        if (filled($sampai) && \Illuminate\Support\Carbon::parse($sampai)->isPast()) {
            return $fitur;
        }

        if ((int) $fitur['harga'] > 0 && filled($harga) && filled($email)
            && $user->email_verified_at
            && strcasecmp((string) $user->email, (string) $email) === 0) {
            $fitur['harga'] = max(1, (int) $harga);
        }

        return $fitur;
    }

    // Marker Order::SUBSCRIPTION_*_FEATURE_ID untuk literal langganan, null kalau bukan langganan.
    private function subscriptionFeatureId(string $featureId): ?int
    {
        return match ($featureId) {
            'langganan', 'langganan-bulanan' => Order::SUBSCRIPTION_MONTHLY_FEATURE_ID,
            'langganan-tahunan'              => Order::SUBSCRIPTION_YEARLY_FEATURE_ID,
            default                          => null,
        };
    }

    // Menerjemahkan {featureId} dari route (angka fitur ATAU literal langganan) menjadi array detail produk.
    private function resolveFitur(string $featureId): ?array
    {
        if ($featureId === 'langganan-tahunan') {
            return [
                'nama'  => __('laporan.paywall.langganan_tahunan_title'),
                'hub'   => 'Langganan',
                'icon'  => '⭐',
                'harga' => config('payment.subscription_yearly_price'),
            ];
        }

        if ($featureId === 'langganan' || $featureId === 'langganan-bulanan') {
            return [
                'nama'  => __('laporan.paywall.langganan_title'),
                'hub'   => 'Langganan',
                'icon'  => '⭐',
                'harga' => config('payment.subscription_monthly_price'),
            ];
        }

        if (!ctype_digit($featureId)) return null;

        return FiturData::cari((int) $featureId);
    }

    private function userDariSession(): ?User
    {
        $userId = Session::get('user_id');
        return $userId ? User::find($userId) : null;
    }
}
