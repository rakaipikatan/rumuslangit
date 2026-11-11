<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AfiliasiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PanduanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SumopodWebhookController;
use App\Http\Controllers\TrialController;
use App\Http\Controllers\TarotController;
use App\Http\Controllers\WilayahController;

// Locale switch
Route::get('/lokal/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Landing + Trial
Route::get('/', [TrialController::class, 'index'])->name('trial.index');
Route::get('/mulai-analisis', [TrialController::class, 'mulai'])->name('trial.mulai');
Route::post('/proses', [TrialController::class, 'proses'])->name('trial.proses');
Route::get('/hasil', [TrialController::class, 'hasil'])->name('trial.hasil');
Route::get('/profil-status', [TrialController::class, 'profilStatus'])->name('trial.profil.status');
Route::get('/analisis', [TrialController::class, 'katalog'])->name('trial.katalog');

// Program afiliasi (publik)
Route::get('/afiliasi', [AfiliasiController::class, 'index'])->name('afiliasi.index');
Route::post('/afiliasi', [AfiliasiController::class, 'daftar'])->middleware('throttle:5,60')->name('afiliasi.daftar');

// Auth
Route::get('/login', [AuthController::class, 'loginForm'])->name('auth.login.form');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
Route::post('/login/kode', [AuthController::class, 'kodeKirim'])->middleware('throttle:6,1')->name('auth.kode.kirim');
Route::post('/login/kode/verifikasi', [AuthController::class, 'kodeVerifikasi'])->middleware('throttle:10,1')->name('auth.kode.verifikasi');
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
Route::get('/pengaturan', [AuthController::class, 'pengaturan'])->name('auth.pengaturan');
Route::post('/pengaturan/password', [AuthController::class, 'passwordSimpan'])->middleware('throttle:5,1')->name('auth.password');

// OTP Email
Route::prefix('otp')->name('otp.')->group(function () {
    Route::post('/kirim', [OtpController::class, 'kirim'])->name('kirim');
    Route::post('/verifikasi', [OtpController::class, 'verifikasi'])->name('verifikasi');
});

// Panduan pengguna (setelah login)
Route::get('/panduan', [PanduanController::class, 'index'])->name('panduan');

// Laporan AI (fitur berbayar)
Route::prefix('laporan')->name('laporan.')->middleware('throttle:10,1')->group(function () {
    Route::get('/{featureId}', [ReportController::class, 'form'])->name('form');
    Route::post('/{featureId}', [ReportController::class, 'proses'])->name('proses');
    Route::get('/{featureId}/status', [ReportController::class, 'status'])->name('status');
    Route::get('/{featureId}/terakhir', [ReportController::class, 'terakhir'])->name('terakhir');
    Route::get('/{featureId}/hasil', [ReportController::class, 'hasil'])->name('hasil');
});

// Tarot card drawing
Route::get('/tarot/pilih', [TarotController::class, 'pilih'])->name('tarot.pilih');

// API Wilayah — cascading dropdowns (tidak butuh auth)
Route::prefix('api/wilayah')->name('api.wilayah.')->group(function () {
    Route::get('/provinsi',   [WilayahController::class, 'provinsi'])->name('provinsi');
    Route::get('/kota',       [WilayahController::class, 'kota'])->name('kota');
    Route::get('/kecamatan',  [WilayahController::class, 'kecamatan'])->name('kecamatan');
    Route::get('/kelurahan',  [WilayahController::class, 'kelurahan'])->name('kelurahan');
});

// Payment (MVP: transfer bank manual + kode unik 3 digit; nanti: integrasi Midtrans/Xendit)
Route::prefix('payment')->name('payment.')->group(function () {
    // Halaman tujuan setelah bayar QRIS — HARUS sebelum /{featureId}
    Route::get('/selesai/{gatewayOrderId}', [PaymentController::class, 'selesai'])->name('selesai');
    Route::get('/{featureId}', [PaymentController::class, 'konfirmasi'])->name('konfirmasi');
    Route::post('/{featureId}', [PaymentController::class, 'proses'])->name('proses');
});

// Webhook SumoPod Managed Payment (QRIS) — dilindungi verifikasi signature, bukan CSRF
Route::post('/webhook/sumopod', [SumopodWebhookController::class, 'handle'])->name('webhook.sumopod');

// Health check untuk monitoring uptime
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'ts' => now()->toIso8601String()]);
});

// ─── Admin Panel ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',  [AdminController::class, 'loginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
    Route::post('/logout',[AdminController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/',              [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users',              [AdminController::class, 'users'])->name('users');
        Route::post('/users/buat-testing',[AdminController::class, 'buatUserTesting'])->name('users.buat-testing');
        Route::get('/users/{id}',         [AdminController::class, 'userDetail'])->whereNumber('id')->name('users.detail');
        Route::post('/users/{id}/beri-jatah',     [AdminController::class, 'beriJatah'])->name('users.beri-jatah');
        Route::post('/users/{id}/beri-langganan', [AdminController::class, 'beriLangganan'])->name('users.beri-langganan');
        Route::post('/users/{id}/reset-password', [AdminController::class, 'resetPasswordUser'])->name('users.reset-password');
        Route::delete('/users/{id}',      [AdminController::class, 'hapusUser'])->name('users.hapus');
        Route::get('/reports',       [AdminController::class, 'reports'])->name('reports');
        Route::get('/email',         [AdminController::class, 'email'])->name('email');
        Route::get('/email/csv',     [AdminController::class, 'emailCsv'])->name('email.csv');
        Route::get('/orders',        [AdminController::class, 'orders'])->name('orders');
        Route::post('/orders/{id}/konfirmasi', [AdminController::class, 'konfirmasiOrder'])->name('orders.konfirmasi');
        Route::post('/orders/{id}/tolak',      [AdminController::class, 'tolakOrder'])->name('orders.tolak');
        Route::post('/orders/{id}/kirim-bukti', [AdminController::class, 'kirimBukti'])->name('orders.kirim-bukti');
        Route::get('/affiliates',              [AdminController::class, 'affiliates'])->name('affiliates');
        Route::post('/affiliates',             [AdminController::class, 'buatAffiliate'])->name('affiliates.buat');
        Route::get('/affiliates/{id}',         [AdminController::class, 'affiliateDetail'])->name('affiliates.detail');
        Route::post('/affiliates/pendaftar/{id}/setuju', [AdminController::class, 'setujuPendaftar'])->name('affiliates.pendaftar.setuju');
        Route::post('/affiliates/pendaftar/{id}/tolak',  [AdminController::class, 'tolakPendaftar'])->name('affiliates.pendaftar.tolak');
        Route::post('/affiliates/{id}/toggle', [AdminController::class, 'toggleStatusAffiliate'])->name('affiliates.toggle');
        Route::post('/affiliates/{id}/lunas',  [AdminController::class, 'tandaiLunasKomisi'])->name('affiliates.lunas');
        Route::get('/system',        [AdminController::class, 'system'])->name('system');
        Route::post('/system/tes-email',         [AdminController::class, 'tesEmail'])->middleware('throttle:10,1')->name('system.tes-email');
        Route::post('/system/tes-gemini',        [AdminController::class, 'tesGemini'])->middleware('throttle:10,1')->name('system.tes-gemini');
        Route::post('/system/clear-cache',       [AdminController::class, 'clearCache'])->name('system.clear-cache');
        Route::post('/system/clear-failed-jobs', [AdminController::class, 'clearFailedJobs'])->name('system.clear-failed-jobs');
    });
});
