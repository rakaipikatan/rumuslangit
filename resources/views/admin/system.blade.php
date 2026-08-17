@extends('admin.layout')
@section('title', 'Sistem')

@section('content')

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
