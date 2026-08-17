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
                    <div class="text-xs text-white/40 mt-1">{{ $fitur['hub'] }} · {{ __('payment.akses_selamanya') }}</div>
                    <div class="text-xs text-white/30 mt-0.5">{{ __('payment.untuk') }} {{ $user->name }}</div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-xl font-bold text-white">Rp {{ number_format($fitur['harga'], 0, ',', '.') }}</div>
                    <div class="text-xs text-white/30">{{ __('payment.sekali_bayar') }}</div>
                </div>
            </div>
        </div>

        @if($order)
        {{-- Status: menunggu verifikasi admin --}}
        <div class="p-5 space-y-4">
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3 flex items-start gap-2">
                <span class="text-amber-400 text-base">⏳</span>
                <div>
                    <div class="text-xs font-medium text-amber-300">{{ __('payment.menunggu_verifikasi') }}</div>
                    <div class="text-xs text-white/40 mt-0.5">{{ __('payment.menunggu_verifikasi_sub') }}</div>
                </div>
            </div>

            @php $rek = collect($rekening)->firstWhere('kode', $order->bank_tujuan); @endphp
            @if($rek)
            <div class="bg-cosmic-700/50 border border-white/10 rounded-xl p-4 space-y-3" x-data="{ copied: false }">
                <div class="flex justify-between text-sm">
                    <span class="text-white/50">{{ __('payment.bank_tujuan') }}</span>
                    <span class="text-white font-medium">{{ $rek['bank'] }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-white/50">{{ __('payment.nomor_rekening') }}</span>
                    <span class="text-white font-mono">{{ $rek['nomor'] }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-white/50">{{ __('payment.atas_nama') }}</span>
                    <span class="text-white">{{ $rek['nama'] }}</span>
                </div>
                <div class="h-px bg-white/5"></div>
                <div class="flex justify-between items-center">
                    <span class="text-white/70 text-sm font-medium">{{ __('payment.total_transfer') }}</span>
                    <div class="text-right">
                        <div class="text-lg font-bold text-white">Rp {{ number_format($order->transfer_amount, 0, ',', '.') }}</div>
                    </div>
                </div>
                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $order->transfer_amount }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="w-full text-xs py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/60 transition">
                    <span x-show="!copied">{{ __('payment.salin_nominal') }}</span>
                    <span x-show="copied" style="display:none">✓ {{ __('payment.tersalin') }}</span>
                </button>
                <p class="text-xs text-amber-300/70 leading-relaxed">
                    {{ __('payment.kode_unik_info', ['kode' => $order->unique_code]) }}
                </p>
            </div>
            @endif

            <p class="text-xs text-white/30 text-center">{{ __('payment.order_id') }}: <span class="font-mono">{{ $order->gateway_order_id }}</span></p>
        </div>
        @else
        {{-- Belum ada order: pilih rekening tujuan --}}
        <form action="{{ route('payment.proses', $featureId) }}" method="POST"
              x-data="{ loading: false, bank: '{{ old('bank', $rekening[0]['kode'] ?? '') }}' }"
              @submit="loading = true">
            @csrf
            <div class="p-5 space-y-3">
                <div class="text-xs text-white/40 uppercase tracking-wider mb-1">{{ __('payment.pilih_rekening') }}</div>
                @foreach($rekening as $rek)
                <label class="flex items-center justify-between gap-3 border rounded-xl px-4 py-3 cursor-pointer transition"
                       :class="bank === '{{ $rek['kode'] }}' ? 'border-purple-500/50 bg-purple-500/10' : 'border-white/10 hover:border-white/20'">
                    <div class="flex items-center gap-3">
                        <input type="radio" name="bank" value="{{ $rek['kode'] }}" x-model="bank" class="accent-purple-500">
                        <div>
                            <div class="text-sm font-medium text-white">{{ $rek['bank'] }}</div>
                            <div class="text-xs text-white/40 font-mono">{{ $rek['nomor'] }} · {{ $rek['nama'] }}</div>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>

            <div class="px-5 pb-5">
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl px-4 py-3 text-xs text-blue-300 leading-relaxed">
                    {{ __('payment.info_kode_unik') }}
                </div>
            </div>

            {{-- Tombol Konfirmasi --}}
            <div class="px-5 pb-5">
                <button
                    type="submit"
                    :disabled="loading"
                    :class="loading ? 'opacity-60 cursor-not-allowed' : 'hover:from-purple-500 hover:to-violet-500'"
                    class="w-full py-4 bg-gradient-to-r from-purple-600 to-violet-600 rounded-2xl font-bold text-base tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40"
                >
                    <span x-show="!loading">{{ __('payment.lanjut_transfer') }}</span>
                    <span x-show="loading" style="display:none">{{ __('payment.memproses') }}</span>
                </button>
            </div>
        </form>
        @endif
    </div>

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
