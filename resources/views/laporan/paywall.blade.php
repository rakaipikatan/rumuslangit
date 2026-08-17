@extends('layouts.app')

@section('title', __('laporan.paywall.title', ['nama' => $fitur['nama']]))

@section('content')
<div class="max-w-lg mx-auto px-4 py-14 text-center">

    <div class="text-4xl mb-4">{{ $fitur['icon'] }}</div>
    <h1 class="text-2xl font-bold mb-2">{{ $fitur['nama'] }}</h1>
    <p class="text-white/40 text-sm mb-8 max-w-sm mx-auto">{{ $fitur['deskripsi'] }}</p>

    <div class="space-y-3 mb-8">

        {{-- Eceran --}}
        <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-5 text-left">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="font-semibold text-sm">{{ __('laporan.paywall.beli_title') }}</div>
                    <div class="text-white/40 text-xs">{{ __('laporan.paywall.beli_sub') }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xl font-bold text-white">Rp {{ number_format($fitur['harga'], 0, ',', '.') }}</div>
                    <div class="text-xs text-white/30">{{ __('laporan.paywall.sekali_bayar') }}</div>
                </div>
            </div>
            <a href="{{ route('payment.konfirmasi', $featureId) }}"
               class="block w-full py-3 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl text-sm font-semibold text-center transition">
                {{ __('laporan.paywall.bayar_sekarang') }}
            </a>
        </div>

        <div class="flex items-center gap-3 text-white/20 text-xs">
            <div class="flex-1 h-px bg-white/10"></div>
            {{ __('laporan.paywall.atau') }}
            <div class="flex-1 h-px bg-white/10"></div>
        </div>

        {{-- Langganan Bulanan --}}
        @php
            $hargaBulanan = config('payment.subscription_monthly_price');
            $hargaTahunan = config('payment.subscription_yearly_price');
            $hematPersen  = $hargaBulanan > 0 ? round((1 - $hargaTahunan / ($hargaBulanan * 12)) * 100) : 0;
        @endphp
        <div class="bg-gradient-to-br from-purple-900/30 to-cyan-900/10 border border-purple-500/30 rounded-2xl p-5 text-left">
            <div class="inline-flex items-center gap-1.5 bg-amber-500/20 border border-amber-500/30 rounded-full px-2.5 py-0.5 text-xs text-amber-300 mb-3">
                {{ __('laporan.paywall.nilai_terbaik') }}
            </div>
            <div class="flex items-center justify-between mb-1">
                <div class="font-semibold text-sm">{{ __('laporan.paywall.langganan_title') }}</div>
                <div class="text-xl font-bold text-white">Rp {{ number_format($hargaBulanan, 0, ',', '.') }}<span class="text-xs text-white/40 font-normal">{{ __('laporan.paywall.langganan_per_bulan') }}</span></div>
            </div>
            <p class="text-white/40 text-xs mb-3">{{ __('laporan.paywall.langganan_desc') }}</p>
            <a href="{{ route('payment.konfirmasi', 'langganan-bulanan') }}"
               class="block w-full py-3 bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl text-sm font-semibold text-center transition">
                {{ __('laporan.paywall.mulai_langganan') }}
            </a>
        </div>

        {{-- Langganan Tahunan --}}
        <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-5 text-left">
            <div class="flex items-center justify-between mb-1">
                <div>
                    <div class="font-semibold text-sm">{{ __('laporan.paywall.langganan_tahunan_title') }}</div>
                    <div class="text-emerald-400 text-xs font-medium">{{ __('laporan.paywall.langganan_tahunan_hemat', ['persen' => $hematPersen]) }}</div>
                </div>
                <div class="text-xl font-bold text-white">Rp {{ number_format($hargaTahunan, 0, ',', '.') }}<span class="text-xs text-white/40 font-normal">{{ __('laporan.paywall.langganan_per_tahun') }}</span></div>
            </div>
            <a href="{{ route('payment.konfirmasi', 'langganan-tahunan') }}"
               class="block w-full py-3 mt-3 bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl text-sm font-semibold text-center transition">
                {{ __('laporan.paywall.mulai_langganan_tahunan') }}
            </a>
        </div>
    </div>

    <a href="{{ route('trial.katalog') }}" class="text-white/25 hover:text-white/40 text-xs transition">
        {{ __('laporan.paywall.kembali_katalog') }}
    </a>

</div>
@endsection
