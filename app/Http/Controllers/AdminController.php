<?php

namespace App\Http\Controllers;

use App\Data\FiturData;
use App\Models\AiReport;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    // ─── Auth ────────────────────────────────────────────────────────────────

    public function loginForm()
    {
        if (Session::get('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Perbandingan constant-time (anti timing attack). ADMIN_PASSWORD boleh plaintext
        // atau hash bcrypt ($2y$...) — keduanya didukung.
        $username = (string) config('admin.username');
        $password = (string) config('admin.password');

        $userOk = $username !== '' && hash_equals($username, (string) $request->username);
        $passOk = $password !== '' && (
            str_starts_with($password, '$2y$')
                ? \Illuminate\Support\Facades\Hash::check((string) $request->password, $password)
                : hash_equals($password, (string) $request->password)
        );
        $validUser = $userOk && $passOk;

        if (!$validUser) {
            return back()->withErrors(['username' => 'Username atau password salah.']);
        }

        $request->session()->regenerate();
        Session::put('admin_logged_in', true);
        Session::put('admin_username', $request->username);
        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        Session::forget(['admin_logged_in', 'admin_username']);
        return redirect()->route('admin.login');
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function dashboard()
    {
        // "Hari" = hari kalender WIB. Pendapatan hanya order lunas berbayar (akses gratis dari admin bernominal 0).
        $awalHari = \App\Support\HariWib::awal();
        $lunas    = fn () => Order::where('status', 'settlement')->where('amount', '>', 0);

        $stats = [
            'total_users'       => User::count(),
            'users_verified'    => User::whereNotNull('email_verified_at')->count(),
            // pelanggan aktif sebenarnya: status aktif DAN masa langganan belum berakhir
            'subscribers_aktif' => User::where('subscription_status', 'active')
                                       ->whereHas('subscriptions', fn ($q) => $q->where('ends_at', '>', now()))->count(),
            'total_reports'     => AiReport::whereNotNull('response_text')->count(),
            'reports_hari_ini'  => AiReport::whereNotNull('response_text')->where('cached_at', '>=', $awalHari)->count(),
            'total_orders'      => Order::where('payment_method', '!=', 'komplimen')->count(),
            'revenue_settled'   => $lunas()->sum('amount'),
            'revenue_hari_ini'  => $lunas()->where('settled_at', '>=', $awalHari)->sum('amount'),
            'revenue_7_hari'    => $lunas()->where('settled_at', '>=', $awalHari->copy()->subDays(6))->sum('amount'),
            'revenue_30_hari'   => $lunas()->where('settled_at', '>=', $awalHari->copy()->subDays(29))->sum('amount'),
            'order_hari_ini'    => $lunas()->where('settled_at', '>=', $awalHari)->count(),
            'pending_jobs'      => DB::table('jobs')->count(),
            'failed_jobs'       => DB::table('failed_jobs')->count(),
        ];

        $recentUsers  = User::latest()->limit(8)->get();
        $recentOrders = Order::with('user')->latest()->limit(8)->get();

        $featureUsage = AiReport::whereNotNull('response_text')
            ->selectRaw('feature_id, count(*) as total')
            ->groupBy('feature_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn($r) => [
                'id'    => $r->feature_id,
                'nama'  => FiturData::cari($r->feature_id, 'id')['nama'] ?? "Fitur #{$r->feature_id}",
                'total' => $r->total,
            ]);

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentOrders', 'featureUsage'));
    }

    // ─── Users ───────────────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $q = User::with(['orders', 'aiReports'])
            ->withCount(['orders as dibeli_count' => fn ($o) => $o->where('status', 'settlement')->where('feature_id', '>', 0)])
            ->latest();

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $q->where(fn($qb) => $qb->where('name', 'ilike', "%{$cari}%")
                                    ->orWhere('email', 'ilike', "%{$cari}%"));
        }

        if ($request->filled('status')) {
            $q->where('subscription_status', $request->status);
        }

        $users = $q->paginate(20)->withQueryString();
        return view('admin.users', compact('users'));
    }

    public function buatUserTesting(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6',
            'dob'           => 'nullable|date',
            'durasi'        => 'required|integer|min:1|max:24',
        ]);

        $user = User::create([
            'name'                => $request->name,
            'jenis_kelamin'       => $request->jenis_kelamin,
            'email'               => $request->email,
            'password'            => bcrypt($request->password),
            'email_verified_at'   => now(),
            'subscription_status' => 'active',
            'dob'                 => $request->dob ?: '1990-01-01',
        ]);

        Subscription::create([
            'user_id'    => $user->id,
            'plan'       => 'monthly',
            'starts_at'  => now(),
            'ends_at'    => now()->addMonths((int) $request->durasi),
            'auto_renew' => false,
        ]);

        $pesan = "User testing dibuat! Email: {$request->email} | Password: {$request->password} | Aktif {$request->durasi} bulan";
        return back()->with('test_user_created', $pesan);
    }

    public function resetPasswordUser(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update(['password' => bcrypt($request->password)]);

        $pesan = "Password berhasil direset! Email: {$user->email} | Password baru: {$request->password}";
        return back()->with('password_reset_info', $pesan);
    }

    // ─── Riwayat email (konsolidasi pemakaian & rekonsiliasi dengan SumoPod) ───

    /** Periode (hari WIB) dari query string: default 30 hari terakhir. Mengembalikan batas dalam zona waktu aplikasi. */
    private function periodeEmail(Request $r): array
    {
        $zona = \App\Support\HariWib::ZONA;
        $valid = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false;

        $sampaiStr = $valid($r->input('sampai')) ? $r->input('sampai') : \App\Support\HariWib::tanggal();
        $dariStr   = $valid($r->input('dari')) ? $r->input('dari')
            : \Illuminate\Support\Carbon::parse($sampaiStr, $zona)->subDays(29)->toDateString();

        return [
            $dariStr,
            $sampaiStr,
            \Illuminate\Support\Carbon::parse($dariStr, $zona)->startOfDay()->setTimezone(config('app.timezone')),
            \Illuminate\Support\Carbon::parse($sampaiStr, $zona)->endOfDay()->setTimezone(config('app.timezone')),
        ];
    }

    private function queryEmail(Request $r, $dari, $sampai)
    {
        $q = \App\Models\EmailLog::whereBetween('created_at', [$dari, $sampai]);

        if ($r->filled('jenis')) $q->where('jenis', $r->input('jenis'));
        if ($r->filled('status') && in_array($r->input('status'), ['terkirim', 'gagal'], true)) $q->where('status', $r->input('status'));
        if ($r->filled('cari')) {
            $cari = '%' . $r->input('cari') . '%';
            $q->where(fn ($qb) => $qb->where('email', 'ilike', $cari)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', $cari)->orWhere('email', 'ilike', $cari)));
        }

        return $q;
    }

    public function email(Request $request)
    {
        [$dariStr, $sampaiStr, $dari, $sampai] = $this->periodeEmail($request);
        $base = fn () => $this->queryEmail($request, $dari, $sampai);

        $ringkasan = [
            'total'    => $base()->count(),
            'terkirim' => $base()->where('status', 'terkirim')->count(),
            'gagal'    => $base()->where('status', 'gagal')->count(),
            'biaya'    => (int) $base()->sum('biaya'),
        ];

        $perJenis = $base()->selectRaw("jenis, sum(case when status = 'terkirim' then 1 else 0 end) as terkirim, sum(case when status = 'gagal' then 1 else 0 end) as gagal, sum(biaya) as biaya")
            ->groupBy('jenis')->orderByDesc('terkirim')->get();

        $perHari = $base()->selectRaw("(created_at AT TIME ZONE 'UTC' AT TIME ZONE 'Asia/Jakarta')::date as hari, sum(case when status = 'terkirim' then 1 else 0 end) as terkirim, sum(case when status = 'gagal' then 1 else 0 end) as gagal, sum(biaya) as biaya")
            ->groupByRaw("(created_at AT TIME ZONE 'UTC' AT TIME ZONE 'Asia/Jakarta')::date")->orderByDesc('hari')->limit(45)->get();

        $topPengguna = $base()->where('status', 'terkirim')->whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as jumlah, sum(biaya) as biaya')->groupBy('user_id')->orderByDesc('jumlah')->limit(15)->get();
        $namaPengguna = User::whereIn('id', $topPengguna->pluck('user_id'))->get(['id', 'name', 'email'])->keyBy('id');

        $tanpaPengguna = [
            'uji'    => $base()->where('status', 'terkirim')->where('jenis', 'like', 'tes%')->count(),
            'lainnya' => $base()->where('status', 'terkirim')->whereNull('user_id')->where('jenis', 'not like', 'tes%')->count(),
        ];

        // Rekonsiliasi: pemakaian menurut dashboard SumoPod (diisi admin) vs catatan lokal
        $sumopod  = $request->filled('sumopod') && ctype_digit((string) $request->input('sumopod')) ? (int) $request->input('sumopod') : null;
        $selisih  = $sumopod !== null ? $sumopod - $ringkasan['terkirim'] : null;

        $daftar = $base()->with('user:id,name')->latest('id')->paginate(50)->withQueryString();

        return view('admin.email', [
            'dariStr' => $dariStr, 'sampaiStr' => $sampaiStr,
            'ringkasan' => $ringkasan, 'perJenis' => $perJenis, 'perHari' => $perHari,
            'topPengguna' => $topPengguna, 'namaPengguna' => $namaPengguna, 'tanpaPengguna' => $tanpaPengguna,
            'sumopod' => $sumopod, 'selisih' => $selisih, 'daftar' => $daftar,
            'jenisList' => \App\Models\EmailLog::JENIS, 'biayaEmail' => \App\Support\EmailLogger::biaya(),
        ]);
    }

    // Ekspor CSV (waktu WIB) mengikuti filter yang sama — untuk pembukuan / pencocokan dengan tagihan SumoPod.
    public function emailCsv(Request $request)
    {
        [$dariStr, $sampaiStr, $dari, $sampai] = $this->periodeEmail($request);
        $q = $this->queryEmail($request, $dari, $sampai)->latest('id');

        $nama = "riwayat-email-{$dariStr}_sd_{$sampaiStr}.csv";

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['waktu_wib', 'jenis', 'penerima', 'pengguna_id', 'status', 'biaya_rp', 'pesan_error']);
            $q->chunkById(500, function ($baris) use ($out) {
                foreach ($baris as $b) {
                    fputcsv($out, [
                        $b->created_at->copy()->timezone(\App\Support\HariWib::ZONA)->format('Y-m-d H:i:s'),
                        $b->jenis, $b->email, $b->user_id, $b->status, $b->biaya, $b->pesan_error,
                    ]);
                }
            });
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ─── Detail pengguna & pemberian akses gratis ─────────────────────────────

    public function userDetail(int $id)
    {
        $user = User::with(['orders' => fn ($q) => $q->latest(), 'subscriptions' => fn ($q) => $q->latest(), 'affiliate'])->findOrFail($id);

        $fitur = FiturData::semua('id');

        // Jatah per fitur untuk fitur yang pernah dibeli / diberikan
        $kuota = [];
        foreach ($user->orders->where('status', 'settlement')->where('feature_id', '>', 0)->pluck('feature_id')->unique() as $fid) {
            $kuota[] = [
                'nama'     => $fitur[$fid]['nama'] ?? "Fitur #{$fid}",
                'total'    => $user->kuotaTotal($fid),
                'terpakai' => $user->kuotaTerpakai($fid),
                'sisa'     => $user->kuotaSisa($fid),
            ];
        }

        $laporan  = AiReport::where('user_id', $id)->latest('id')->limit(15)->get();
        $fiturBerbayar = collect($fitur)->filter(fn ($f) => ($f['harga'] ?? 0) > 0 && empty($f['freemium']) && empty($f['subscriber_only']));

        $emailStat = [
            'jumlah'  => \App\Models\EmailLog::where('user_id', $id)->where('status', 'terkirim')->count(),
            'biaya'   => (int) \App\Models\EmailLog::where('user_id', $id)->sum('biaya'),
            'gagal'   => \App\Models\EmailLog::where('user_id', $id)->where('status', 'gagal')->count(),
            'terbaru' => \App\Models\EmailLog::where('user_id', $id)->latest('id')->limit(10)->get(),
            'perJenis' => \App\Models\EmailLog::where('user_id', $id)->where('status', 'terkirim')->selectRaw('jenis, count(*) as n')->groupBy('jenis')->orderByDesc('n')->get(),
        ];

        return view('admin.user_detail', compact('user', 'fitur', 'kuota', 'laporan', 'fiturBerbayar', 'emailStat'));
    }

    // Beri N paket jatah gratis (tiap paket = payment.single_generations jatah) untuk satu fitur berbayar.
    public function beriJatah(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $idBerbayar = collect(FiturData::semua('id'))
            ->filter(fn ($f) => ($f['harga'] ?? 0) > 0 && empty($f['freemium']) && empty($f['subscriber_only']))
            ->keys()->all();

        $request->validate([
            'feature_id' => ['required', 'integer', \Illuminate\Validation\Rule::in($idBerbayar)],
            'paket'      => 'required|integer|min:1|max:10',
            'catatan'    => 'nullable|string|max:200',
        ]);

        for ($i = 0; $i < (int) $request->paket; $i++) {
            $this->buatOrderKomplimen($user, (int) $request->feature_id, $request->catatan);
        }

        $jatah = (int) $request->paket * (int) config('payment.single_generations', 3);
        $nama  = FiturData::cari((int) $request->feature_id, 'id')['nama'] ?? 'fitur';

        return back()->with('success', "{$jatah} jatah gratis untuk \"{$nama}\" diberikan ke {$user->name}.");
    }

    // Beri/perpanjang langganan gratis (1, 3, 6, atau 12 bulan), diperpanjang dari masa aktif yang ada.
    public function beriLangganan(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'bulan'   => 'required|integer|in:1,3,6,12',
            'catatan' => 'nullable|string|max:200',
        ]);

        $bulan = (int) $request->bulan;
        app(\App\Services\OrderSettlementService::class)->aktifkanLangganan($user, $bulan);
        $this->buatOrderKomplimen(
            $user,
            $bulan >= 12 ? Order::SUBSCRIPTION_YEARLY_FEATURE_ID : Order::SUBSCRIPTION_MONTHLY_FEATURE_ID,
            $request->catatan ?: "Langganan gratis {$bulan} bulan dari admin"
        );

        return back()->with('success', "Langganan {$bulan} bulan diberikan ke {$user->name}.");
    }

    // Catatan akses gratis: order lunas bernominal 0 (tidak masuk pendapatan, tanpa komisi afiliasi, tanpa email bukti bayar).
    private function buatOrderKomplimen(User $user, int $featureId, ?string $catatan): Order
    {
        return Order::create([
            'user_id'          => $user->id,
            'feature_id'       => $featureId,
            'amount'           => 0,
            'payment_method'   => 'komplimen',
            'gateway_order_id' => 'GRANT-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'status'           => 'settlement',
            'settled_at'       => now(),
            'catatan'          => $catatan ?: 'Akses gratis dari admin',
        ]);
    }

    // Kirim ulang email bukti pembayaran (mis. pembeli tidak menerima / masuk spam).
    public function kirimBukti(int $id, \App\Services\OrderSettlementService $settlement)
    {
        $order = Order::with('user')->findOrFail($id);

        if (!$order->isSettled() || $order->amount <= 0 || $order->payment_method === 'komplimen') {
            return back()->with('error', 'Bukti bayar hanya untuk order berbayar yang sudah lunas.');
        }

        if (!$order->user?->email) {
            return back()->with('error', 'Pengguna ini tidak punya email.');
        }

        $settlement->kirimBuktiBayar($order);

        return back()->with('success', "Bukti bayar order {$order->gateway_order_id} dikirim ulang ke {$order->user->email}.");
    }

    public function hapusUser(int $id)
    {
        $user = User::findOrFail($id);
        DB::table('pdf_downloads')->where('user_id', $id)->delete();
        DB::table('subscriptions')->where('user_id', $id)->delete();
        DB::table('orders')->where('user_id', $id)->delete();
        DB::table('ai_reports')->where('user_id', $id)->delete();
        DB::table('questionnaire_sessions')->where('user_id', $id)->delete();
        DB::table('birth_profiles')->where('user_id', $id)->delete();
        $user->delete();

        return back()->with('success', "User #{$id} berhasil dihapus.");
    }

    // ─── Reports ─────────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $q = AiReport::with('user')
            ->whereNotNull('response_text')
            ->latest('cached_at');

        if ($request->filled('fitur')) {
            $q->where('feature_id', $request->fitur);
        }

        $reports     = $q->paginate(20)->withQueryString();
        $fiturList   = FiturData::semua('id');

        $statsPerFitur = AiReport::whereNotNull('response_text')
            ->selectRaw('feature_id, count(*) as total')
            ->groupBy('feature_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'id'    => $r->feature_id,
                'nama'  => FiturData::cari($r->feature_id, 'id')['nama'] ?? "Fitur #{$r->feature_id}",
                'total' => $r->total,
            ]);

        return view('admin.reports', compact('reports', 'fiturList', 'statsPerFitur'));
    }

    // ─── Orders ──────────────────────────────────────────────────────────────

    public function orders(Request $request)
    {
        $q = Order::with('user')->latest();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $orders  = $q->paginate(25)->withQueryString();
        $revenue = Order::where('status', 'settlement')->sum('amount');

        return view('admin.orders', compact('orders', 'revenue'));
    }

    // Cocokkan nominal masuk (amount + unique_code) dengan mutasi rekening lalu buka akses fitur
    public function konfirmasiOrder(int $id, \App\Services\OrderSettlementService $settlement)
    {
        $order = Order::findOrFail($id);

        $settlement->settle($order);

        return back()->with('success', "Order #{$order->id} dikonfirmasi lunas.");
    }

    public function tolakOrder(int $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'pending') {
            $order->update(['status' => 'cancel']);
        }

        return back()->with('success', "Order #{$order->id} ditolak/dibatalkan.");
    }

    // ─── Affiliates ──────────────────────────────────────────────────────────

    public function affiliates()
    {
        $affiliates = Affiliate::withCount('users')
            ->withSum(['commissions as total_unpaid' => fn ($q) => $q->where('status', 'unpaid')], 'komisi_amount')
            ->withSum(['commissions as total_paid' => fn ($q) => $q->where('status', 'paid')], 'komisi_amount')
            ->withSum(['commissions as total_payable' => fn ($q) => $q->where('status', 'unpaid')->where('created_at', '<=', $this->batasTahanKomisi())], 'komisi_amount')
            ->latest()
            ->get();

        // Tabel pendaftar baru ada setelah migration; jangan 500 bila belum dimigrasi.
        $pendaftar = \Illuminate\Support\Facades\Schema::hasTable('affiliate_applications')
            ? AffiliateApplication::where('status', 'pending')->oldest()->get()
            : collect();

        return view('admin.affiliates', compact('affiliates', 'pendaftar'));
    }

    public function buatAffiliate(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:30',
            'referral_code'  => 'required|string|max:30|alpha_dash|unique:affiliates,referral_code',
            'bank_nama'      => 'nullable|string|max:30',
            'bank_rekening'  => 'nullable|string|max:50',
            'bank_atas_nama' => 'nullable|string|max:100',
            'komisi_persen'  => 'required|integer|min:1|max:100',
        ]);

        Affiliate::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'referral_code'  => strtoupper($request->referral_code),
            'bank_nama'      => $request->bank_nama,
            'bank_rekening'  => $request->bank_rekening,
            'bank_atas_nama' => $request->bank_atas_nama,
            'komisi_persen'  => $request->komisi_persen,
            'status'         => 'active',
        ]);

        return back()->with('success', 'Afiliator baru berhasil ditambahkan.');
    }

    // Setujui pendaftar: buat afiliator aktif + kode referral otomatis, lalu kirim email berisi link.
    public function setujuPendaftar(Request $request, int $id)
    {
        $request->validate(['komisi_persen' => 'nullable|integer|min:1|max:100']);

        $app = AffiliateApplication::where('status', 'pending')->findOrFail($id);

        $affiliate = Affiliate::create([
            'name'           => $app->name,
            'email'          => $app->email,
            'phone'          => $app->phone,
            'referral_code'  => $this->buatKodeReferral($app->name),
            'bank_nama'      => $app->bank_nama,
            'bank_rekening'  => $app->bank_rekening,
            'bank_atas_nama' => $app->bank_atas_nama,
            'komisi_persen'  => (int) ($request->komisi_persen ?: config('payment.affiliate_default_commission', 20)),
            'status'         => 'active',
        ]);

        $app->update(['status' => 'approved', 'affiliate_id' => $affiliate->id, 'reviewed_at' => now()]);

        $pesan = "{$app->name} disetujui. Kode referral: {$affiliate->referral_code}.";
        try {
            \Illuminate\Support\Facades\Mail::to($app->email)->send(new \App\Mail\AffiliateDisetujuiMail($affiliate));
            $pesan .= ' Email berisi link referral sudah dikirim.';
        } catch (\Throwable $e) {
            \Log::warning('Email persetujuan afiliasi gagal', ['affiliate' => $affiliate->id, 'error' => $e->getMessage()]);
            \App\Support\LayananStatus::gagal('email', "Persetujuan afiliasi #{$affiliate->id}: " . $e->getMessage());
            \App\Support\EmailLogger::catatGagal('afiliasi_disetujui', $app->email, $e->getMessage());
            $pesan .= ' PERHATIAN: email gagal terkirim — kirim link manual: ' . url('/') . '?ref=' . $affiliate->referral_code;
        }

        return back()->with('success', $pesan);
    }

    public function tolakPendaftar(int $id)
    {
        $app = AffiliateApplication::where('status', 'pending')->findOrFail($id);
        $app->update(['status' => 'rejected', 'reviewed_at' => now()]);

        return back()->with('success', "Pendaftaran {$app->name} ditolak.");
    }

    // Kode referral unik dari nama depan: 3–6 huruf + 2 digit (mis. BUDI42).
    private function buatKodeReferral(string $nama): string
    {
        $depan = strtoupper(preg_replace('/[^A-Za-z]/', '', \Illuminate\Support\Str::ascii(strtok(trim($nama), ' ') ?: '')));
        $dasar = strlen($depan) >= 3 ? substr($depan, 0, 6) : 'RL' . $depan;

        do {
            $kode = $dasar . random_int(10, 99);
        } while (Affiliate::where('referral_code', $kode)->exists());

        return $kode;
    }

    public function affiliateDetail(int $id)
    {
        $affiliate = Affiliate::findOrFail($id);

        $komisi = $affiliate->commissions()
            ->with(['user', 'order'])
            ->latest()
            ->paginate(25);

        $totalUnpaid  = $affiliate->commissions()->where('status', 'unpaid')->sum('komisi_amount');
        $totalPaid    = $affiliate->commissions()->where('status', 'paid')->sum('komisi_amount');
        $totalPayable = $affiliate->commissions()->where('status', 'unpaid')->where('created_at', '<=', $this->batasTahanKomisi())->sum('komisi_amount');
        $totalDitahan = $totalUnpaid - $totalPayable;
        $holdDays     = (int) config('payment.affiliate_hold_days', 14);

        return view('admin.affiliate_detail', compact('affiliate', 'komisi', 'totalUnpaid', 'totalPaid', 'totalPayable', 'totalDitahan', 'holdDays'));
    }

    public function toggleStatusAffiliate(int $id)
    {
        $affiliate = Affiliate::findOrFail($id);
        $affiliate->update(['status' => $affiliate->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', "Status afiliator {$affiliate->name} diubah jadi {$affiliate->status}.");
    }

    // Komisi lebih baru dari batas ini masih ditahan (belum boleh dibayar).
    private function batasTahanKomisi(): \Illuminate\Support\Carbon
    {
        return now()->subDays((int) config('payment.affiliate_hold_days', 14));
    }

    // Tandai komisi yang SUDAH LEWAT masa tahan sebagai lunas — dipakai saat transfer manual tanggal 25 selesai.
    // Komisi yang masih ditahan tidak ikut ditandai (akan masuk periode berikutnya).
    public function tandaiLunasKomisi(int $id)
    {
        $affiliate = Affiliate::findOrFail($id);

        $siap = $affiliate->commissions()->where('status', 'unpaid')->where('created_at', '<=', $this->batasTahanKomisi());
        $jumlah = (clone $siap)->count();

        $siap->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', "{$jumlah} komisi afiliator {$affiliate->name} ditandai lunas (komisi yang masih ditahan tidak ikut).");
    }

    // ─── System ──────────────────────────────────────────────────────────────

    public function system()
    {
        $info = [
            'laravel'      => app()->version(),
            'php'          => PHP_VERSION,
            'environment'  => app()->environment(),
            'debug'        => config('app.debug') ? 'Ya' : 'Tidak',
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'mail_driver'  => config('mail.default'),
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs'  => DB::table('failed_jobs')->count(),
            'db_size'      => $this->dbSize(),
        ];

        // ── Status layanan ──
        $layanan = [];
        foreach (array_keys(\App\Support\LayananStatus::LAYANAN) as $k) {
            $layanan[$k] = \App\Support\LayananStatus::ringkas($k);
        }

        $detak       = \App\Support\LayananStatus::detakTerakhir();
        $penjadwalOk = $detak && $detak->gt(now()->subMinutes(5));

        try {
            $master = collect(app(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)->all());
            $horizon = $master->isEmpty() ? 'mati' : ($master->contains(fn ($m) => ($m->status ?? '') === 'paused') ? 'dijeda' : 'berjalan');
        } catch (\Throwable $e) {
            $horizon = 'tidak diketahui';
        }

        $konfigurasi = [
            'Kunci Gemini'           => filled(config('services.gemini.key')),
            'Kunci SumoPod'          => filled(config('services.sumopod_pay.api_key')),
            'Secret webhook SumoPod' => filled(config('services.sumopod_pay.webhook_secret')),
            'Host SMTP'              => filled(config('mail.mailers.smtp.host')),
        ];
        $modeSumopod = str_contains((string) config('services.sumopod_pay.base_url'), 'sandbox') ? 'SANDBOX (uji)' : 'LIVE';

        // Peringatan: harga uji masih aktif (jangan sampai tertinggal di produksi)
        $peringatan = [];
        $sampai = config('payment.test_until');
        $hargaUjiAktif = filled(config('payment.test_price')) && filled(config('payment.test_email'))
            && (blank($sampai) || \Illuminate\Support\Carbon::parse($sampai)->isFuture());
        if ($hargaUjiAktif) {
            $peringatan[] = 'HARGA UJI AKTIF: Rp' . number_format((int) config('payment.test_price'), 0, ',', '.')
                . ' untuk satu email pengujian'
                . (filled($sampai) ? ', otomatis mati ' . \Illuminate\Support\Carbon::parse($sampai)->timezone('Asia/Jakarta')->format('d/m H:i') . ' WIB' : ' — TANPA batas waktu')
                . '. Matikan setelah tes selesai (beri tanda # di .env, lalu config:cache).';
        }

        $cadangan = [
            'aktif'  => app(\App\Services\GrokService::class)->tersedia(),
            'kunci'  => filled(config('services.xai.key')),
            'model'  => (string) config('services.xai.model'),
            'jeda'   => \Illuminate\Support\Facades\Cache::has(\App\Services\GeminiService::KUNCI_JEDA),
        ];

        return view('admin.system', compact('info', 'layanan', 'detak', 'penjadwalOk', 'horizon', 'konfigurasi', 'modeSumopod', 'peringatan', 'cadangan'));
    }

    // Tombol "Tes kirim email": kirim email uji ke alamat yang diketik admin dan tampilkan hasilnya apa adanya.
    public function tesEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'Ini email uji dari panel admin Rumus Langit. Jika Anda menerimanya, pengiriman email berfungsi. '
                . 'Tidak ada di Inbox? Cek folder Spam atau Junk.',
                function ($m) use ($request) {
                    $m->to($request->email)->subject('Uji Email Rumus Langit (admin)');
                    \App\Support\EmailLogger::tandai($m->getSymfonyMessage(), 'tes_admin');
                }
            );
        } catch (\Throwable $e) {
            \App\Support\LayananStatus::gagal('email', 'Tes admin: ' . $e->getMessage());
            \App\Support\EmailLogger::catatGagal('tes_admin', $request->email, $e->getMessage());
            return back()->with('error', 'GAGAL kirim email uji: ' . $e->getMessage());
        }

        return back()->with('success', "Email uji diterima server SMTP dan dikirim ke {$request->email}. Cek Inbox/Spam dalam 1–2 menit.");
    }

    // Tombol "Tes Grok": menampilkan model yang tersedia untuk akun ini lalu satu panggilan uji dengan model yang dipilih.
    public function tesGrok()
    {
        if (blank(config('services.xai.key'))) {
            return back()->with('error', 'Kunci AI cadangan belum diisi di .env (XAI_API_KEY).');
        }

        // Salah kunci/alamat adalah kekeliruan paling umum: Groq (groq.com, kunci gsk_) ≠ Grok milik xAI (kunci xai-).
        $kunci = (string) config('services.xai.key');
        $url   = (string) config('services.xai.base_url');
        if (str_starts_with($kunci, 'gsk_') && str_contains($url, 'x.ai')) {
            return back()->with('error', 'Kunci berawalan "gsk_" adalah kunci GroqCloud (groq.com), BUKAN xAI Grok, sehingga ditolak xAI. '
                . 'Dua pilihan: (1) isi XAI_BASE_URL=https://api.groq.com/openai/v1 di .env lalu jalankan config:cache; atau (2) pakai kunci xAI (berawalan "xai-") dari console.x.ai.');
        }
        if (str_starts_with($kunci, 'xai-') && str_contains($url, 'groq.com')) {
            return back()->with('error', 'Kunci berawalan "xai-" adalah kunci xAI, tetapi XAI_BASE_URL menunjuk ke Groq. Ubah XAI_BASE_URL=https://api.x.ai/v1 (atau hapus barisnya) lalu config:cache.');
        }

        $grok = app(\App\Services\GrokService::class);

        try {
            $ids = $grok->daftarModel();
        } catch (\Throwable $e) {
            \App\Support\LayananStatus::gagal('grok', 'Tes admin: ' . $e->getMessage());
            return back()->with('error', 'AI cadangan tidak terjangkau / kunci ditolak: ' . $e->getMessage());
        }

        $daftar = implode(', ', array_slice($ids, 0, 25)) ?: '(tidak ada)';
        $model  = (string) config('services.xai.model');

        if ($model === '') {
            return back()->with('error', "XAI_MODEL belum diisi di .env. Model yang tersedia untuk akun ini: {$daftar}");
        }
        if (!in_array($model, $ids, true)) {
            return back()->with('error', "Model \"{$model}\" tidak ada di akun ini. Model yang tersedia: {$daftar}");
        }

        try {
            [$balasan, $ms] = $grok->uji();
        } catch (\Throwable $e) {
            return back()->with('error', 'AI cadangan menolak panggilan uji: ' . $e->getMessage());
        }

        return back()->with('success', "AI cadangan berfungsi (model {$model}, {$ms} ms; menghasilkan JSON valid: " . \Illuminate\Support\Str::limit($balasan, 90) . "). Model tersedia: {$daftar}");
    }

    // Tombol "Tes Gemini": satu panggilan kecil untuk memastikan kunci, model, dan koneksi berfungsi.
    public function tesGemini()
    {
        $kunci = config('services.gemini.key');
        if (blank($kunci)) {
            return back()->with('error', 'Kunci Gemini belum diisi di .env (GEMINI_API_KEY).');
        }

        $model = config('services.gemini.model');
        $mulai = microtime(true);

        try {
            $r = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders(['x-goog-api-key' => $kunci])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents'         => [['parts' => [['text' => 'Balas satu kata: OK']]]],
                    'generationConfig' => ['maxOutputTokens' => 20],
                ]);
        } catch (\Throwable $e) {
            \App\Support\LayananStatus::gagal('gemini', 'Tes admin: ' . $e->getMessage());
            return back()->with('error', 'Gemini tidak terjangkau: ' . $e->getMessage());
        }

        $ms = (int) round((microtime(true) - $mulai) * 1000);

        if ($r->failed()) {
            \App\Support\LayananStatus::gagal('gemini', "Tes admin: HTTP {$r->status()}");
            return back()->with('error', "Gemini menolak (HTTP {$r->status()}): " . substr($r->body(), 0, 200));
        }

        \App\Support\LayananStatus::ok('gemini', 'tes admin');

        return back()->with('success', "Gemini berfungsi (model {$model}, {$ms} ms).");
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        return back()->with('success', 'Cache berhasil dibersihkan.');
    }

    public function clearFailedJobs()
    {
        DB::table('failed_jobs')->delete();
        return back()->with('success', 'Failed jobs berhasil dibersihkan.');
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function dbSize(): string
    {
        try {
            $dbName = config('database.connections.pgsql.database');
            $result = DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            return $result->size ?? '-';
        } catch (\Throwable) {
            return '-';
        }
    }
}
