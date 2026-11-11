@extends('admin.layout')
@section('title', 'Sistem')

@section('content')

{{-- ═══ Status layanan ═══ --}}
@php
    $wibS = fn ($t) => $t ? $t->copy()->timezone('Asia/Jakarta')->format('d/m H:i') . ' WIB' : null;
    $rel  = fn ($t) => $t ? $t->copy()->locale('id')->diffForHumans() : null;
    $semuaBaik = empty($peringatan) && collect($layanan)->every(fn ($l) => !$l['perlu_cek']) && $penjadwalOk && $horizon === 'berjalan' && collect($konfigurasi)->every(fn ($v) => $v);
@endphp
<div class="bg-cosmic-800/60 border {{ $semuaBaik ? 'border-emerald-500/20' : 'border-amber-500/30' }} rounded-2xl p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider">Status layanan</h2>
        <span class="text-xs px-2.5 py-1 rounded-full {{ $semuaBaik ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20' : 'bg-amber-500/15 text-amber-300 border border-amber-500/25' }}">
            {{ $semuaBaik ? '✓ Semua normal' : '⚠ Ada yang perlu dicek' }}
        </span>
    </div>

    @foreach($peringatan as $w)
    <div class="mb-4 bg-amber-500/10 border border-amber-500/30 rounded-xl px-4 py-3 text-xs text-amber-300 leading-relaxed">⚠ {{ $w }}</div>
    @endforeach

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-white/30 uppercase tracking-wider">
                <th class="py-2 pr-4">Layanan</th><th class="pr-4">Status</th><th class="pr-4">Terakhir berhasil</th><th class="pr-4">Gagal 24 jam</th><th>Kegagalan terakhir</th>
            </tr></thead>
            <tbody class="divide-y divide-white/5">
            @foreach($layanan as $l)
                <tr>
                    <td class="py-2.5 pr-4 text-white/80">{{ $l['label'] }}</td>
                    <td class="pr-4">
                        @if($l['perlu_cek'])<span class="text-xs text-amber-300">⚠ perlu dicek</span>@if($l['catatan'])<div class="text-xs text-amber-300/70 max-w-[14rem]">{{ $l['catatan'] }}</div>@endif
                        @elseif($l['terakhir_ok'])<span class="text-xs text-emerald-300">✓ normal</span>
                        @else<span class="text-xs text-white/30">belum ada data</span>@endif
                    </td>
                    <td class="pr-4 text-white/50 text-xs">
                        @if($l['terakhir_ok']){{ $rel($l['terakhir_ok']) }}<div class="text-white/25">{{ $wibS($l['terakhir_ok']) }}{{ $l['info_ok'] ? ' · ' . $l['info_ok'] : '' }}</div>@else — @endif
                    </td>
                    <td class="pr-4 {{ $l['gagal_24j'] > 0 ? 'text-amber-300' : 'text-white/40' }}">{{ $l['gagal_24j'] }}</td>
                    <td class="text-white/40 text-xs">
                        @if($l['gagal_terakhir'])<span class="text-white/30">{{ $wibS($l['gagal_terakhir']->created_at) }}</span> — {{ \Illuminate\Support\Str::limit($l['gagal_terakhir']->pesan, 120) }}@else — @endif
                    </td>
                </tr>
            @endforeach
                <tr>
                    <td class="py-2.5 pr-4 text-white/80">Penjadwal (cron)</td>
                    <td class="pr-4">@if($penjadwalOk)<span class="text-xs text-emerald-300">✓ hidup</span>@else<span class="text-xs text-amber-300">⚠ tidak berdetak</span>@endif</td>
                    <td class="pr-4 text-white/50 text-xs" colspan="3">{{ $detak ? 'detak terakhir ' . $rel($detak) . ' (' . $wibS($detak) . ')' : 'belum pernah berdetak — cek cron schedule:run; Hoki Harian tidak akan terkirim' }}</td>
                </tr>
                <tr>
                    <td class="py-2.5 pr-4 text-white/80">Antrean (Horizon)</td>
                    <td class="pr-4">@if($horizon === 'berjalan')<span class="text-xs text-emerald-300">✓ berjalan</span>@else<span class="text-xs text-amber-300">⚠ {{ $horizon }}</span>@endif</td>
                    <td class="pr-4 text-white/50 text-xs" colspan="3">menunggu {{ $info['pending_jobs'] }} · gagal {{ $info['failed_jobs'] }}{{ $horizon !== 'berjalan' ? ' — laporan AI & email antrean tidak diproses' : '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-xs">
        @foreach($konfigurasi as $nama => $ada)
            <span class="{{ $ada ? 'text-white/50' : 'text-amber-300' }}">{{ $ada ? '✓' : '✕' }} {{ $nama }} {{ $ada ? 'terisi' : 'KOSONG' }}</span>
        @endforeach
        <span class="{{ str_contains($modeSumopod, 'SANDBOX') ? 'text-amber-300' : 'text-white/50' }}">SumoPod: {{ $modeSumopod }}</span>
    </div>

    <div class="mt-4 text-xs {{ $cadangan['aktif'] ? 'text-white/50' : 'text-white/35' }}">
        Cadangan AI: <strong class="{{ $cadangan['aktif'] ? 'text-emerald-300' : 'text-white/50' }}">{{ $cadangan['aktif'] ? 'AKTIF — model ' . $cadangan['model'] : 'belum diaktifkan' }}</strong>
        @if(!$cadangan['aktif'])
            — {{ $cadangan['kunci'] ? 'isi XAI_MODEL di .env' : 'isi XAI_API_KEY dan XAI_MODEL di .env, lalu tekan "Tes Grok"' }}
        @endif
        @if($cadangan['jeda'])<span class="text-amber-300"> · Gemini sedang dilewati (kuota harian habis) — permintaan langsung ke cadangan</span>@endif
    </div>

    <div class="mt-6 pt-5 border-t border-white/5 grid grid-cols-1 md:grid-cols-2 gap-5">
        <form method="POST" action="{{ route('admin.system.tes-email') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <div class="flex-1 min-w-[12rem]">
                <label class="block text-xs text-white/40 mb-1">Tes kirim email ke</label>
                <input type="email" name="email" required placeholder="alamat@email.com"
                       class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500/60">
            </div>
            <button class="px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm text-white transition">Kirim email uji</button>
        </form>
        <div class="flex flex-wrap items-end gap-2">
            <form method="POST" action="{{ route('admin.system.tes-gemini') }}">
                @csrf
                <button class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm text-white/80 transition">Tes Gemini</button>
            </form>
            <form method="POST" action="{{ route('admin.system.tes-grok') }}">
                @csrf
                <button class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm text-white/80 transition">Tes AI cadangan</button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Info Sistem --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl p-6">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-5">Info Aplikasi</h2>
        <dl class="space-y-3">
            @foreach ([
                ['Laravel',        $info['laravel']],
                ['PHP',            $info['php']],
                ['Environment',    $info['environment']],
                ['Debug Mode',     $info['debug']],
                ['Cache Driver',   $info['cache_driver']],
                ['Queue Driver',   $info['queue_driver']],
                ['Mail Driver',    $info['mail_driver']],
                ['Ukuran DB',      $info['db_size']],
            ] as [$label, $val])
            <div class="flex items-center justify-between py-2 border-b border-white/5">
                <dt class="text-sm text-white/40">{{ $label }}</dt>
                <dd class="text-sm text-white/80 font-mono">{{ $val }}</dd>
            </div>
            @endforeach
        </dl>
    </div>

    {{-- Queue Status --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl p-6">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-5">Queue & Jobs</h2>

        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-amber-300">{{ $info['pending_jobs'] }}</div>
                <div class="text-xs text-white/40 mt-1">Pending Jobs</div>
            </div>
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold {{ $info['failed_jobs'] > 0 ? 'text-red-400' : 'text-emerald-300' }}">{{ $info['failed_jobs'] }}</div>
                <div class="text-xs text-white/40 mt-1">Failed Jobs</div>
            </div>
        </div>

        @if ($info['failed_jobs'] > 0)
        <form method="POST" action="{{ route('admin.system.clear-failed-jobs') }}" class="mb-4"
              onsubmit="return confirm('Hapus semua failed jobs?')">
            @csrf
            <button type="submit"
                class="w-full py-2.5 bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 rounded-xl text-sm text-red-300 transition">
                🗑 Hapus Semua Failed Jobs
            </button>
        </form>
        @endif

        <div class="text-xs text-white/30 leading-relaxed">
            Queue driver: <span class="text-white/50">{{ $info['queue_driver'] }}</span>.
            Jalankan worker dengan: <code class="text-purple-300/70">php artisan queue:work</code>
        </div>
    </div>

    {{-- Cache Management --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl p-6">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-5">Cache & View</h2>
        <p class="text-sm text-white/40 mb-5 leading-relaxed">
            Membersihkan cache aplikasi dan view yang sudah di-compile.
            Gunakan setelah perubahan config atau deployment.
        </p>
        <form method="POST" action="{{ route('admin.system.clear-cache') }}">
            @csrf
            <button type="submit"
                class="w-full py-2.5 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/30 rounded-xl text-sm text-purple-300 transition">
                🧹 Clear Cache & Views
            </button>
        </form>
    </div>

    {{-- Artisan Commands --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl p-6">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-5">Commands Berguna</h2>
        <div class="space-y-2 text-xs font-mono text-white/40">
            @foreach ([
                'php artisan queue:work --tries=3',
                'php artisan schedule:run',
                'php artisan hoki-harian:kirim',
                'php artisan migrate --force',
                'php artisan cache:clear',
                'php artisan optimize',
            ] as $cmd)
            <div class="bg-black/30 rounded-lg px-3 py-2 flex items-center gap-2">
                <span class="text-purple-400/60">$</span>
                <span>{{ $cmd }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
