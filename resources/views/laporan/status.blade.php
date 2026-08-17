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

    <p class="text-white/20 text-xs">{{ __('laporan.status.durasi', ['durasi' => $featureId == 12 ? '5–10' : '10–30']) }}</p>

</div>

@push('scripts')
<script>
function statusPage() {
    return {
        langkah: 0,
        interval: null,
        timerLangkah: null,

        mulaiPolling() {
            // Animasi langkah setiap 4 detik
            this.timerLangkah = setInterval(() => {
                if (this.langkah < 3) this.langkah++;
            }, 4000);

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
