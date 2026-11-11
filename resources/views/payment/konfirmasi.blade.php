@extends('layouts.app')

@section('title', __('payment.title', ['nama' => $fitur['nama']]))

@section('content')
<div class="max-w-md mx-auto px-4 py-14">

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="text-4xl mb-3">{{ $fitur['icon'] }}</div>
        <h1 class="text-2xl font-bold mb-1">{{ __('payment.heading') }}</h1>
        <p class="text-white/40 text-sm">{{ __('payment.subtitle') }}</p>
    </div>

    @if(session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 mb-6 flex items-center gap-3 text-emerald-300 text-sm">
        <span class="text-lg">✓</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Kartu Detail Order --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl overflow-hidden mb-5">

        {{-- Detail fitur --}}
        <div class="p-5 border-b border-white/5">
            <div class="text-xs text-white/40 uppercase tracking-wider mb-3">{{ __('payment.detail_pesanan') }}</div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="font-semibold text-sm text-white">{{ $fitur['nama'] }}</div>
                    <div class="text-xs text-white/40 mt-1">{{ $fitur['hub'] }} · {{ str_starts_with($featureId, 'langganan') ? __('payment.akses_langganan') : __('payment.akses_selamanya', ['n' => config('payment.single_generations', 3)]) }}</div>
                    <div class="text-xs text-white/30 mt-0.5">{{ __('payment.untuk') }} {{ $user->name }}</div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-xl font-bold text-white">Rp {{ number_format($fitur['harga'], 0, ',', '.') }}</div>
                    <div class="text-xs text-white/30">{{ $featureId === 'langganan-tahunan' ? __('payment.per_tahun') : (str_starts_with($featureId, 'langganan') ? __('payment.per_bulan') : __('payment.sekali_bayar')) }}</div>
                </div>
            </div>
        </div>

        @if($order)
        {{-- Order QRIS menunggu pembayaran --}}
        <div class="p-5 space-y-4">
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3 flex items-start gap-2">
                <span class="text-amber-400 text-base">⏳</span>
                <div>
                    <div class="text-xs font-medium text-amber-300">{{ __('payment.qris_menunggu') }}</div>
                    <div class="text-xs text-white/40 mt-0.5">{{ __('payment.qris_menunggu_sub') }}</div>
                </div>
            </div>
            <a href="{{ $order->payment_link_url }}" target="_blank" rel="noopener"
               class="block w-full text-center py-4 bg-gradient-to-r from-purple-600 to-violet-600 rounded-2xl font-bold text-base tracking-wide shadow-lg shadow-purple-900/40 hover:from-purple-500 hover:to-violet-500 transition-all">
                {{ __('payment.qris_bayar_sekarang') }}
            </a>
            <a href="{{ route('payment.selesai', $order->gateway_order_id) }}" class="block text-center text-xs text-white/50 hover:text-white/70 transition">
                {{ __('payment.qris_cek_status') }}
            </a>
            @if($order->payment_expires_at)
            <p class="text-xs text-white/30 text-center">{{ __('payment.qris_berlaku_sampai') }} {{ $order->payment_expires_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>
            @endif
            <p class="text-xs text-white/30 text-center">{{ __('payment.order_id') }}: <span class="font-mono">{{ $order->gateway_order_id }}</span></p>
        </div>
        @else
        {{-- Belum ada order: bayar dengan QRIS --}}
        <form action="{{ route('payment.proses', $featureId) }}" method="POST"
              x-data="{ loading: false }" @submit="loading = true">
            @csrf

            @if($errors->has('metode'))
            <div class="mx-5 mt-5 bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-300 text-xs">{{ $errors->first('metode') }}</div>
            @endif

            <div class="p-5 pb-0">
                <div class="flex items-center gap-3 border border-purple-500/50 bg-purple-500/10 rounded-xl px-4 py-3">
                    <span class="text-2xl">▦</span>
                    <div>
                        <div class="text-sm font-medium text-white">QRIS</div>
                        <div class="text-xs text-white/40">{{ __('payment.metode_qris_sub') }}</div>
                    </div>
                </div>
            </div>

            <div class="px-5 pt-3">
                <p class="text-xs text-white/35 leading-relaxed">{{ __('payment.qris_catatan') }}</p>
            </div>

            <div class="p-5">
                @if($qrisTersedia)
                <button
                    type="submit"
                    :disabled="loading"
                    :class="loading ? 'opacity-60 cursor-not-allowed' : 'hover:from-purple-500 hover:to-violet-500'"
                    class="w-full py-4 bg-gradient-to-r from-purple-600 to-violet-600 rounded-2xl font-bold text-base tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40"
                >
                    <span x-show="!loading">{{ __('payment.lanjut_qris') }}</span>
                    <span x-show="loading" style="display:none">{{ __('payment.memproses') }}</span>
                </button>
                @else
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-300 text-xs">{{ __('payment.qris_tidak_tersedia') }}</div>
                @endif
            </div>
        </form>
        @endif
    </div>

    @if(str_starts_with($featureId, 'langganan') && !$order)
        @php
            $hBulanan = (int) config('payment.subscription_monthly_price');
            $hTahunan = (int) config('payment.subscription_yearly_price');
            $hemat    = $hBulanan > 0 ? (int) round((1 - $hTahunan / ($hBulanan * 12)) * 100) : 0;
        @endphp
        <div class="text-center mt-3">
            @if($featureId === 'langganan-tahunan')
                <a href="{{ route('payment.konfirmasi', 'langganan-bulanan') }}" class="text-xs text-purple-300/80 hover:text-purple-200 underline underline-offset-2 transition">
                    {{ __('payment.ganti_ke_bulanan', ['harga' => number_format($hBulanan, 0, ',', '.')]) }}
                </a>
            @else
                <a href="{{ route('payment.konfirmasi', 'langganan-tahunan') }}" class="text-xs text-purple-300/80 hover:text-purple-200 underline underline-offset-2 transition">
                    {{ __('payment.ganti_ke_tahunan', ['harga' => number_format($hTahunan, 0, ',', '.'), 'persen' => $hemat]) }}
                </a>
            @endif
        </div>
    @endif

    @unless($order)
    <p class="text-center text-white/25 text-xs mt-4 leading-relaxed">
        {{ __('payment.persetujuan') }}<br>
        <strong class="text-white/40">Rp {{ number_format($fitur['harga'], 0, ',', '.') }}</strong> {{ __('payment.untuk_laporan') }} <strong class="text-white/40">{{ $fitur['nama'] }}</strong>.
    </p>
    @endunless

    <div class="text-center mt-4">
        <a href="{{ route('trial.katalog') }}" class="text-white/25 hover:text-white/40 text-xs transition">
            {{ __('payment.batal_kembali') }}
        </a>
    </div>

</div>
@endsection
