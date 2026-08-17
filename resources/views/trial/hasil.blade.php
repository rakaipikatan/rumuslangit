@extends('layouts.app')

@section('title', __('hasil.title', ['name' => $user->name]))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10" x-data="hasilPage()">

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/30 rounded-full px-4 py-1.5 text-xs text-emerald-300 mb-4">
            {{ __('hasil.kalkulasi_berhasil') }}
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold mb-2">
            {{ __('hasil.peta_energi') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-cyan-400">{{ $user->name }}</span>
        </h1>
        <p class="text-white/40 text-sm">
            {{ \Carbon\Carbon::parse($user->dob)->locale(app()->getLocale())->isoFormat('D MMMM Y') }} · {{ __('hasil.pukul') }} {{ str_pad($user->birth_hour, 2, '0', STR_PAD_LEFT) }}.00 {{ __('hasil.wib') }}
        </p>
    </div>

    {{-- Identitas Kosmik --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        @foreach ([
            ['Weton', $kalkulasi['weton']['weton'], __('hasil.neptu') . ' ' . $kalkulasi['weton']['neptu'], 'text-purple-300'],
            ['Shio',  $kalkulasi['shio']['nama'],   __('hasil.tahun') . ' ' . \Carbon\Carbon::parse($user->dob)->format('Y'),   'text-cyan-300'],
            ['Zodiak',$kalkulasi['zodiak'],          __('hasil.elemen') . ' ' . $kalkulasi['elemen'],         'text-amber-300'],
        ] as [$label, $nilai, $sub, $color])
        <div class="bg-cosmic-800/60 border border-white/10 rounded-xl p-4 text-center">
            <div class="text-xs text-white/40 uppercase tracking-widest mb-1">{{ $label }}</div>
            <div class="font-bold text-sm {{ $color }}">{{ $nilai }}</div>
            <div class="text-xs text-white/30 mt-0.5">{{ $sub }}</div>
        </div>
        @endforeach
    </div>

    {{-- Radar Chart --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 mb-6">
        <h2 class="text-sm font-semibold text-white/60 uppercase tracking-wider mb-5 flex items-center gap-2">
            <span class="text-purple-400">◈</span> {{ __('hasil.radar_title') }}
        </h2>
        <div class="relative h-64"><canvas id="radarChart"></canvas></div>
        <div class="grid grid-cols-5 gap-2 mt-4">
            @foreach (['karir'=>'text-blue-400','asmara'=>'text-pink-400','cuan'=>'text-amber-400','kesehatan'=>'text-green-400','spiritual'=>'text-purple-400'] as $key=>$color)
            @php $nama = __('hasil.radar.' . $key); @endphp
            <div class="text-center">
                <div class="text-lg font-bold {{ $color }}">{{ $kalkulasi['radar'][$key] }}</div>
                <div class="text-xs text-white/30">{{ $nama }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- PROFIL KARAKTER AI                                             --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl overflow-hidden mb-6">

        {{-- Header seksi --}}
        <div class="flex items-center gap-2 px-6 pt-6 pb-4 border-b border-white/5">
            <span class="text-cyan-400">◈</span>
            <h2 class="text-sm font-semibold text-white/60 uppercase tracking-wider">{{ __('hasil.profil_karakter') }}</h2>
        </div>

        {{-- STATE: Loading --}}
        <div id="profil-loading" class="p-8 text-center" @if($profilData) style="display:none" @endif>
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full border border-purple-500/30 mb-4">
                <div class="w-5 h-5 border-2 border-purple-400 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <p class="text-white/50 text-sm">{{ __('hasil.profil_loading') }}</p>
            <p class="text-white/25 text-xs mt-1">{{ __('hasil.profil_loading_sub') }}</p>
        </div>

        {{-- STATE: Konten AI --}}
        <div id="profil-konten" class="p-6" @if(!$profilData) style="display:none" @endif>
            @if($profilData)
            @include('trial.partials.profil-karakter', ['p' => $profilData])
            @endif
        </div>
    </div>

    {{-- Audit Energi Wilayah --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 mb-8">
        <h2 class="text-sm font-semibold text-white/60 uppercase tracking-wider mb-4 flex items-center gap-2">
            <span class="text-amber-400">◈</span> {{ __('hasil.audit_wilayah_title') }}
        </h2>
        @php
            $elemenWilayah = ['Kayu','Api','Tanah','Logam','Air'][abs(crc32($user->kelurahan)) % 5];
            $selarasMap    = ['Kayu'=>['Air','Kayu'],'Api'=>['Kayu','Api'],'Tanah'=>['Api','Tanah'],'Logam'=>['Tanah','Logam'],'Air'=>['Logam','Air']];
            $cocok         = in_array($elemenWilayah, $selarasMap[$kalkulasi['elemen']] ?? []);
            $sangatSelaras = $elemenWilayah === $kalkulasi['elemen'];
            $statusWilayah = $cocok ? __('hasil.status_selaras') : ($sangatSelaras ? __('hasil.status_sangat_selaras') : __('hasil.status_netral'));
            $statusWarna   = ($cocok || $sangatSelaras)
                ? 'text-emerald-300 bg-emerald-500/10 border-emerald-500/30'
                : 'text-amber-300 bg-amber-500/10 border-amber-500/30';
        @endphp
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-white">{{ $user->kelurahan }}, {{ $user->kecamatan }}</p>
                <p class="text-xs text-white/40 mt-0.5">{{ __('hasil.elemen_wilayah') }} <strong class="text-white/60">{{ $elemenWilayah }}</strong></p>
            </div>
            <span class="border rounded-full px-3 py-1 text-xs font-medium {{ $statusWarna }}">{{ $statusWilayah }}</span>
        </div>
    </div>

    {{-- CTA --}}
    <div class="relative bg-gradient-to-br from-purple-900/40 to-cyan-900/20 border border-purple-500/30 rounded-2xl p-6 text-center overflow-hidden">
        <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 50% 0%,rgba(124,58,237,.15) 0%,transparent 60%)"></div>
        <div class="relative">
            <div class="text-2xl mb-3">🔮</div>
            <h3 class="font-bold text-lg mb-2">{{ __('hasil.cta_title') }}</h3>
            <p class="text-white/50 text-sm mb-5 max-w-sm mx-auto">{{ __('hasil.cta_desc') }}</p>

            @if (session('otp_verified') || $user->email_verified_at)
                {{-- Sudah verifikasi — langsung ke katalog --}}
                <a href="{{ route('trial.katalog') }}"
                   class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200 shadow-lg shadow-purple-900/40">
                    {{ __('hasil.cta_pilih_analisis') }}
                </a>
                <p class="text-emerald-400/60 text-xs mt-3">{{ __('hasil.email_terverifikasi') }}</p>
            @else
                {{-- Belum verifikasi — tampil OTP modal --}}
                <button type="button" @click.prevent="bukaModalOTP()"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200 shadow-lg shadow-purple-900/40">
                    {{ __('hasil.cta_akses_lebih_dalam') }}
                </button>
                <p class="text-white/25 text-xs mt-3">{{ __('hasil.verifikasi_gratis') }}</p>
            @endif
        </div>
    </div>

{{-- Modal OTP --}}
<div x-show="showOTP"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="tutupModal()"></div>
    <div class="relative bg-cosmic-800 border border-white/10 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
        <button type="button" @click.prevent="tutupModal()" class="absolute top-4 right-4 text-white/30 hover:text-white/60">✕</button>
        <div class="text-center mb-5">
            <div class="text-3xl mb-2">✉️</div>
            <h3 class="font-bold text-lg">{{ __('hasil.otp.title') }}</h3>
            <p class="text-white/40 text-xs mt-1">{{ __('hasil.otp.subtitle') }}</p>
        </div>
        <div x-show="step===1" class="space-y-4">
            <input type="email" x-model="emailInput" placeholder="{{ __('hasil.otp.email_placeholder') }}" @keyup.enter.prevent="kirimOTP()"
                class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/20 text-sm focus:outline-none focus:border-purple-500/60 transition">
            <div x-show="pesanError" class="text-red-300 text-xs bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2" x-text="pesanError"></div>
            <button type="button" @click.prevent="kirimOTP()" :disabled="loading"
                class="w-full py-3 bg-gradient-to-r from-purple-600 to-violet-600 disabled:opacity-50 rounded-xl font-semibold text-sm transition">
                <span x-show="!loading">{{ __('hasil.otp.kirim_kode') }}</span><span x-show="loading">{{ __('hasil.otp.mengirim') }}</span>
            </button>
        </div>
        <div x-show="step===2" class="space-y-4">
            <p class="text-white/50 text-xs text-center">{{ __('hasil.otp.kode_info') }}</p>
            <input type="text" x-model="kodeOTP" placeholder="● ● ● ● ● ●" maxlength="6" @keyup.enter.prevent="verifikasiOTP()"
                class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-center text-xl tracking-widest focus:outline-none focus:border-purple-500/60 transition">
            <div x-show="pesanError" class="text-red-300 text-xs bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2" x-text="pesanError"></div>
            <button type="button" @click.prevent="verifikasiOTP()" :disabled="loading"
                class="w-full py-3 bg-gradient-to-r from-purple-600 to-violet-600 disabled:opacity-50 rounded-xl font-semibold text-sm transition">
                <span x-show="!loading">{{ __('hasil.otp.verifikasi_lanjutkan') }}</span><span x-show="loading">{{ __('hasil.otp.memverifikasi') }}</span>
            </button>
            <button type="button" @click.prevent="step=1;kodeOTP='';pesanError=''" class="w-full text-white/30 text-xs hover:text-white/50 transition">{{ __('hasil.otp.ubah_email') }}</button>
        </div>
    </div>
</div>

</div>

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
const i18nHasil = {
    profilJs: @json(__('hasil.profil_js')),
    otp: @json(__('hasil.otp')),
};

// Radar chart
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('radarChart').getContext('2d'), {
        type: 'radar',
        data: {
            labels: Object.values(@json(__('hasil.radar'))),
            datasets: [{
                data: [{{ $kalkulasi['radar']['karir'] }},{{ $kalkulasi['radar']['asmara'] }},{{ $kalkulasi['radar']['cuan'] }},{{ $kalkulasi['radar']['kesehatan'] }},{{ $kalkulasi['radar']['spiritual'] }}],
                backgroundColor: 'rgba(124,58,237,0.15)', borderColor: 'rgba(124,58,237,0.7)',
                pointBackgroundColor: 'rgba(124,58,237,1)', pointBorderColor: '#fff', borderWidth: 2,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { r: { min:0, max:100, ticks:{display:false}, grid:{color:'rgba(255,255,255,0.06)'}, angleLines:{color:'rgba(255,255,255,0.06)'}, pointLabels:{color:'rgba(255,255,255,0.5)',font:{size:11}} } },
            plugins: { legend:{display:false} }
        }
    });

    // Polling profil AI jika belum siap
    @if(!$profilData && $reportIdProfil)
    const polling = setInterval(async () => {
        try {
            const res  = await fetch('{{ route('trial.profil.status') }}', { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (json.siap && json.data) {
                clearInterval(polling);
                renderProfil(json.data);
            }
        } catch(_) {}
    }, 2500);
    @endif
});

function renderProfil(p) {
    const container = document.getElementById('profil-konten');
    const loader    = document.getElementById('profil-loading');

    const tag  = (arr, cls) => arr.map(t => `<span class="${cls}">${t}</span>`).join('');
    const li   = (arr) => arr.map(t => `<li class="flex items-start gap-2"><span class="text-purple-400 mt-0.5">◆</span><span>${t}</span></li>`).join('');
    const kom  = (arr) => arr.map(k => `
        <div class="flex items-start gap-3">
            <div class="text-xs text-white/30 pt-0.5 w-32 flex-shrink-0">${k.sistem}</div>
            <div class="text-sm text-white/80">→ <span class="text-cyan-300">${k.karakter}</span></div>
        </div>`).join('');

    container.innerHTML = `
        {{-- Tipe --}}
        <div class="text-center mb-6 pb-6 border-b border-white/5">
            <div class="inline-block bg-gradient-to-r from-purple-500/20 to-cyan-500/20 border border-purple-500/30 rounded-2xl px-5 py-3 mb-3">
                <div class="text-xs text-white/40 uppercase tracking-widest mb-1">${i18nHasil.profilJs.tipe_karakter}</div>
                <div class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-cyan-300">${p.tipe_nama}</div>
            </div>
            <p class="text-white/60 text-sm italic">"${p.tipe_tagline}"</p>
        </div>

        {{-- Kombinasi sistem --}}
        <div class="mb-5">
            <h3 class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">${i18nHasil.profilJs.kombinasi_title}</h3>
            <div class="space-y-2 bg-cosmic-700/30 rounded-xl p-4">${kom(p.kombinasi)}</div>
            <p class="text-white/60 text-sm mt-3 leading-relaxed">${p.hasil_gabungan}</p>
        </div>

        {{-- Karakteristik --}}
        <div class="mb-5">
            <h3 class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">${i18nHasil.profilJs.karakteristik_title}</h3>
            <ul class="space-y-1.5 text-sm text-white/70">${li(p.karakteristik)}</ul>
        </div>

        {{-- Catatan unik --}}
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3 mb-5">
            <p class="text-amber-200 text-sm leading-relaxed">💡 ${p.catatan_unik}</p>
        </div>

        {{-- Kekuatan & Perlu Dijaga --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4">
                <h3 class="text-xs font-semibold text-emerald-300 uppercase tracking-widest mb-2">${i18nHasil.profilJs.kekuatan_title}</h3>
                <div class="flex flex-wrap gap-1.5">${tag(p.kekuatan, 'text-xs bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 rounded-full px-2.5 py-1')}</div>
            </div>
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4">
                <h3 class="text-xs font-semibold text-red-300 uppercase tracking-widest mb-2">${i18nHasil.profilJs.perlu_dijaga_title}</h3>
                <div class="flex flex-wrap gap-1.5">${tag(p.perlu_dijaga, 'text-xs bg-red-500/10 text-red-300 border border-red-500/20 rounded-full px-2.5 py-1')}</div>
            </div>
        </div>

        {{-- Sisi Spiritual --}}
        <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-4 mb-5">
            <h3 class="text-xs font-semibold text-purple-300 uppercase tracking-widest mb-2">${i18nHasil.profilJs.spiritual_title}</h3>
            <p class="text-white/70 text-sm leading-relaxed">${p.sisi_spiritual}</p>
        </div>

        {{-- Potensi tersembunyi --}}
        <div class="bg-cyan-500/10 border border-cyan-500/20 rounded-xl p-4 mb-4">
            <h3 class="text-xs font-semibold text-cyan-300 uppercase tracking-widest mb-2">${i18nHasil.profilJs.potensi_title}</h3>
            <p class="text-white/70 text-sm leading-relaxed">${p.potensi_tersembunyi}</p>
        </div>

        {{-- Pesan penutup --}}
        <p class="text-center text-white/50 text-sm italic pt-2 border-t border-white/5 mt-2">${p.pesan_penutup} ✦</p>
    `;

    loader.style.display = 'none';
    container.style.display = 'block';
    container.style.animation = 'fadeIn .4s ease';
}

function hasilPage() {
    return {
        showOTP: false, step: 1, emailInput: '', kodeOTP: '', pesanError: '', loading: false,
        bukaModalOTP() { this.showOTP = true; this.step = 1; this.pesanError = ''; },
        tutupModal()   { this.showOTP = false; },
        async kirimOTP() {
            this.pesanError = '';
            if (!this.emailInput || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.emailInput)) {
                this.pesanError = i18nHasil.otp.err_email_invalid; return;
            }
            this.loading = true;
            try {
                const res  = await fetch('{{ route('otp.kirim') }}', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify({email:this.emailInput}) });
                if (res.status === 419) { this.pesanError = i18nHasil.otp.err_session_expired; return; }
                let data;
                try { data = await res.json(); } catch(_) { this.pesanError = i18nHasil.otp.err_server.replace(':status', res.status); return; }
                data.success ? (this.step = 2, this.kodeOTP = '', this.pesanError = '') : (this.pesanError = data.message || i18nHasil.otp.err_kirim_gagal);
            } catch(e) { this.pesanError = i18nHasil.otp.err_koneksi; }
            finally { this.loading = false; }
        },
        async verifikasiOTP() {
            this.pesanError = '';
            if (this.kodeOTP.length !== 6) { this.pesanError = i18nHasil.otp.err_kode_6_digit; return; }
            this.loading = true;
            try {
                const res  = await fetch('{{ route('otp.verifikasi') }}', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify({kode_otp:this.kodeOTP}) });
                if (res.status === 419) { this.pesanError = i18nHasil.otp.err_session_expired; return; }
                let data;
                try { data = await res.json(); } catch(_) { this.pesanError = i18nHasil.otp.err_server.replace(':status', res.status); return; }
                data.success ? (window.location.href = data.redirect) : (this.pesanError = data.message || i18nHasil.otp.err_kode_invalid);
            } catch(e) { this.pesanError = i18nHasil.otp.err_koneksi; }
            finally { this.loading = false; }
        },
    }
}
</script>
<style>@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}</style>
@endpush
@endsection
