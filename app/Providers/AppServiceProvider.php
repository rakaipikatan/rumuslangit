<?php

namespace App\Providers;

use App\Support\LayananStatus;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Panel admin "Status layanan": setiap email yang berhasil dikirim memperbarui penanda "terakhir sukses".
        Event::listen(MessageSent::class, fn () => LayananStatus::ok('email'));

        // Pekerjaan antrean yang gagal (mis. bukti bayar/email gagal terkirim di Horizon) → dicatat sebagai kegagalan.
        Event::listen(JobFailed::class, fn (JobFailed $e) => LayananStatus::dariJobGagal($e->job->resolveName(), $e->exception));
    }
}
