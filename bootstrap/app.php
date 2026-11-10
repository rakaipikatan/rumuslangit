<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Kirim hoki harian ke semua subscriber pukul 06.00 WIB
        $schedule->command('hoki:kirim')->dailyAt('06:00')->timezone('Asia/Jakarta');

        // Detak penjadwal tiap menit (panel "Status layanan" memantau bahwa cron hidup)
        $schedule->call(fn () => \App\Support\LayananStatus::detak())->everyMinute()->name('detak-penjadwal');

        // Buang catatan kegagalan layanan yang lebih dari 30 hari
        $schedule->call(fn () => \App\Models\ServiceEvent::where('created_at', '<', now()->subDays(30))->delete())->daily()->name('bersihkan-service-events');

        // Sinkronisasi status subscription yang expired setiap jam
        $schedule->command('subscription:sync')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\CaptureReferral::class,
        ]);

        // Webhook dari server SumoPod tidak membawa token CSRF; diamankan lewat signature Svix.
        $middleware->preventRequestForgery(except: ['webhook/sumopod']);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
