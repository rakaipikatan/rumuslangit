@extends('layouts.app')

@section('title', __('laporan.paywall.pilih_paket_title'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-14">

    <div class="text-center mb-8">
        <div class="text-4xl mb-3">⭐</div>
        <h1 class="text-2xl font-bold mb-2">{{ __('laporan.paywall.pilih_paket_title') }}</h1>
        <p class="text-white/40 text-sm">{{ __('hasil.katalog.langganan_title') }}</p>
    </div>

    <ul class="max-w-md mx-auto mb-8 space-y-1.5 text-sm text-white/60">
        @foreach(['fitur_semua_laporan', 'fitur_angka_warna', 'fitur_hoki'] as $k)
        <li class="flex gap-2"><span class="text-emerald-400">✓</span><span>{{ __('laporan.paywall.' . $k) }}</span></li>
        @endforeach
        <li class="flex gap-2"><span class="text-emerald-400">✓</span><span>{{ __('laporan.paywall.fitur_batas', ['n' => config('payment.subscriber_daily_reports', 20)]) }}</span></li>
    </ul>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        {{-- Bulanan --}}
        <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 flex flex-col">
            <div class="font-semibold text-sm mb-1">{{ __('laporan.paywall.langganan_title') }}</div>
            <div class="text-3xl font-bold text-white mb-1">Rp {{ number_format($bulanan, 0, ',', '.') }}<span class="text-sm text-white/40 font-normal">{{ __('laporan.paywall.langganan_per_bulan') }}</span></div>
            <div class="text-xs text-white/30 mb-5">&nbsp;</div>
            <a href="{{ route('payment.konfirmasi', 'langganan-bulanan') }}"
               class="mt-auto block w-full py-3 bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl text-sm font-semibold text-center transition">
                {{ __('laporan.paywall.mulai_langganan') }}
            </a>
        </div>

        {{-- Tahunan --}}
        <div class="relative bg-gradient-to-br from-purple-900/40 to-cyan-900/20 border border-purple-500/40 rounded-2xl p-6 flex flex-col">
            <div class="absolute -top-3 left-6 bg-amber-500/90 text-black text-xs font-semibold rounded-full px-3 py-0.5">{{ __('laporan.paywall.nilai_terbaik') }}</div>
            <div class="font-semibold text-sm mb-1">{{ __('laporan.paywall.langganan_tahunan_title') }}</div>
            <div class="text-3xl font-bold text-white mb-1">Rp {{ number_format($tahunan, 0, ',', '.') }}<span class="text-sm text-white/40 font-normal">{{ __('laporan.paywall.langganan_per_tahun') }}</span></div>
            <div class="text-xs mb-5">
                <span class="text-emerald-400 font-medium">{{ __('laporan.paywall.langganan_tahunan_hemat', ['persen' => $hemat]) }}</span>
                <span class="text-white/40"> · {{ __('laporan.paywall.langganan_setara_bulan', ['nilai' => number_format($setara, 0, ',', '.')]) }}</span>
            </div>
            <a href="{{ route('payment.konfirmasi', 'langganan-tahunan') }}"
               class="mt-auto block w-full py-3 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl text-sm font-semibold text-center shadow-lg shadow-purple-900/40 transition">
                {{ __('laporan.paywall.mulai_langganan_tahunan') }}
            </a>
        </div>
    </div>

    <p class="text-center">
        <a href="{{ route('trial.katalog') }}" class="text-white/25 hover:text-white/40 text-xs transition">{{ __('payment.batal_kembali') }}</a>
    </p>
</div>
@endsection
