@extends('layouts.app')

@section('title', __('laporan.status.title'))

@section('content')
<div class="max-w-lg mx-auto px-4 py-20 text-center" x-data="statusPage()" x-init="mulaiPolling()">

    {{-- Animasi --}}
    <div class="relative inline-flex items-center justify-center w-24 h-24 mb-8">
        <div class="absolute inset-0 rounded-full border-2 border-purple-500/20 animate-ping"></div>
        <div class="absolute inset-2 rounded-full border-2 border-purple-500/30 animate-ping" style="animation-delay: 0.3s"></div>
        <div class="relative text-4xl">🔮</div>
    </div>

    <h1 class="text-xl font-bold mb-2">{{ __('laporan.status.heading') }}</h1>
    <p class="text-white/40 text-sm mb-8 max-w-sm mx-auto">
        {{ __('laporan.status.desc') }}
    </p>

    {{-- Langkah proses --}}
    <div class="text-left max-w-xs mx-auto space-y-3 mb-10">
        @foreach (__('laporan.status.steps') as $i => $step)
        <div class="flex items-center gap-3 text-sm"
             :class="{{ $i }} < langkah ? 'text-emerald-300' : '{{ $i }} === langkah ? text-white' : 'text-white/25'">
            <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs border flex-shrink-0"
                  :class="{{ $i }} < langkah ? 'bg-emerald-500/20 border-emerald-500/50' : '{{ $i }} === langkah ? border-purple-500/60 border-purple-500/20' : 'border-white/10'">
                <span x-show="{{ $i }} < langkah">✓</span>
                <span x-show="{{ $i }} === langkah" class="w-2 h-2 bg-purple-400 rounded-full animate-pulse"></span>
            </span>
            <span>{{ $step }}</span>
        </div>
        @endforeach
    </div>

    <div x-show="lama" style="display:none" class="mt-6 bg-amber-500/10 border border-amber-500/25 rounded-xl px-4 py-3 text-xs text-amber-200 leading-relaxed text-left">
        {{ __('laporan.status.lama') }}
        <a href="{{ route('trial.katalog') }}" class="block mt-2 text-purple-300 underline underline-offset-2">{{ __('laporan.status.lama_katalog') }}</a>
    </div>

    <p class="text-white/20 text-xs mt-4">{{ __('laporan.status.durasi', ['durasi' => $featureId == 12 ? '5–10' : '10–30']) }}</p>

</div>

@push('scripts')
<script>
function statusPage() {
    return {
        langkah: 0,
        lama: false,
        interval: null,
        timerLangkah: null,

        mulaiPolling() {
            // Animasi langkah setiap 4 detik
            this.timerLangkah = setInterval(() => {
                if (this.langkah < 3) this.langkah++;
            }, 4000);

            // Lebih dari 3 menit: layanan AI mungkin sedang padat — beri tahu pengguna dengan jujur
            setTimeout(() => { this.lama = true; }, 180000);

            // Polling setiap 2 detik
            this.interval = setInterval(async () => {
                try {
                    const res  = await fetch(window.location.href, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (data.siap) {
                        clearInterval(this.interval);
                        clearInterval(this.timerLangkah);
                        this.langkah = 4;
                        window.location.href = data.redirect;
                    }
                } catch (_) {}
            }, 2000);
        }
    }
}
</script>
@endpush
@endsection
