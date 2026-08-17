<?php

namespace App\Http\Controllers;

use App\Data\FiturData;
use App\Models\AiReport;
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

        $validUser = $request->username === config('admin.username')
            && $request->password === config('admin.password');

        if (!$validUser) {
            return back()->withErrors(['username' => 'Username atau password salah.']);
        }

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
        $stats = [
            'total_users'       => User::count(),
            'users_verified'    => User::whereNotNull('email_verified_at')->count(),
            'subscribers_aktif' => User::where('subscription_status', 'active')->count(),
            'total_reports'     => AiReport::whereNotNull('response_text')->count(),
            'reports_hari_ini'  => AiReport::whereNotNull('response_text')->whereDate('cached_at', today())->count(),
            'total_orders'      => Order::count(),
            'revenue_settled'   => Order::where('status', 'settlement')->sum('amount'),
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
    public function konfirmasiOrder(int $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'pending') {
            $order->update(['status' => 'settlement', 'settled_at' => now()]);
        }

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

        return view('admin.system', compact('info'));
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
