{{-- Dipakai saat profil sudah siap di server-side (refresh halaman) --}}
@php $p = $p ?? []; @endphp

{{-- Tipe Karakter --}}
<div class="text-center mb-6 pb-6 border-b border-white/5">
    <div class="inline-block bg-gradient-to-r from-purple-500/20 to-cyan-500/20 border border-purple-500/30 rounded-2xl px-5 py-3 mb-3">
        <div class="text-xs text-white/40 uppercase tracking-widest mb-1">{{ __('hasil.profil_js.tipe_karakter') }}</div>
        <div class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-cyan-300">
            {{ $p['tipe_nama'] ?? '' }}
        </div>
    </div>
    <p class="text-white/60 text-sm italic">"{{ $p['tipe_tagline'] ?? '' }}"</p>
</div>

{{-- Kombinasi 3 Sistem --}}
<div class="mb-5">
    <h3 class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">{{ __('hasil.profil_js.kombinasi_title') }}</h3>
    <div class="space-y-2 bg-cosmic-700/30 rounded-xl p-4">
        @foreach ($p['kombinasi'] ?? [] as $k)
        <div class="flex items-start gap-3">
            <div class="text-xs text-white/30 pt-0.5 w-36 flex-shrink-0">{{ $k['sistem'] }}</div>
            <div class="text-sm text-white/80">→ <span class="text-cyan-300">{{ $k['karakter'] }}</span></div>
        </div>
        @endforeach
    </div>
    <p class="text-white/60 text-sm mt-3 leading-relaxed">{{ $p['hasil_gabungan'] ?? '' }}</p>
</div>

{{-- Tipe Orang Yang --}}
<div class="mb-5">
    <h3 class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">{{ __('hasil.profil_js.karakteristik_title') }}</h3>
    <ul class="space-y-1.5 text-sm text-white/70">
        @foreach ($p['karakteristik'] ?? [] as $k)
        <li class="flex items-start gap-2">
            <span class="text-purple-400 mt-0.5 flex-shrink-0">◆</span>
            <span>{{ $k }}</span>
        </li>
        @endforeach
    </ul>
</div>

{{-- Catatan Unik --}}
@if(!empty($p['catatan_unik']))
<div class="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3 mb-5">
    <p class="text-amber-200 text-sm leading-relaxed">💡 {{ $p['catatan_unik'] }}</p>
</div>
@endif

{{-- Kekuatan & Perlu Dijaga --}}
<div class="grid grid-cols-2 gap-4 mb-5">
    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4">
        <h3 class="text-xs font-semibold text-emerald-300 uppercase tracking-widest mb-2">{{ __('hasil.profil_js.kekuatan_title') }}</h3>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($p['kekuatan'] ?? [] as $k)
            <span class="text-xs bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 rounded-full px-2.5 py-1">{{ $k }}</span>
            @endforeach
        </div>
    </div>
    <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4">
        <h3 class="text-xs font-semibold text-red-300 uppercase tracking-widest mb-2">{{ __('hasil.profil_js.perlu_dijaga_title') }}</h3>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($p['perlu_dijaga'] ?? [] as $k)
            <span class="text-xs bg-red-500/10 text-red-300 border border-red-500/20 rounded-full px-2.5 py-1">{{ $k }}</span>
            @endforeach
        </div>
    </div>
</div>

{{-- Sisi Spiritual --}}
<div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-4 mb-5">
    <h3 class="text-xs font-semibold text-purple-300 uppercase tracking-widest mb-2">{{ __('hasil.profil_js.spiritual_title') }}</h3>
    <p class="text-white/70 text-sm leading-relaxed">{{ $p['sisi_spiritual'] ?? '' }}</p>
</div>

{{-- Potensi Tersembunyi --}}
@if(!empty($p['potensi_tersembunyi']))
<div class="bg-cyan-500/10 border border-cyan-500/20 rounded-xl p-4 mb-4">
    <h3 class="text-xs font-semibold text-cyan-300 uppercase tracking-widest mb-2">{{ __('hasil.profil_js.potensi_title') }}</h3>
    <p class="text-white/70 text-sm leading-relaxed">{{ $p['potensi_tersembunyi'] }}</p>
</div>
@endif

{{-- Pesan Penutup --}}
@if(!empty($p['pesan_penutup']))
<p class="text-center text-white/50 text-sm italic pt-2 border-t border-white/5 mt-2">
    {{ $p['pesan_penutup'] }} ✦
</p>
@endif
