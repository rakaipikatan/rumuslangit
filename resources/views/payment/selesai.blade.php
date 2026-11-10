@extends('layouts.app')

@section('title', __('payment.selesai_title'))

@section('content')
<div class="max-w-md mx-auto px-4 py-20 text-center"
     x-data="{ lambat: false }"
     x-init="
        const url = '{{ route('payment.selesai', $order->gateway_order_id) }}';
        const mulai = Date.now();
        const cek = async () => {
            try {
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.redirect) { window.location.href = j.redirect; return; }
            } catch (_) {}
            if (Date.now() - mulai > 90000) lambat = true;
            setTimeout(cek, 2500);
        };
        cek();
     ">

    <div class="text-5xl mb-5 animate-pulse">✦</div>
    <h1 class="text-xl font-bold mb-2">{{ __('payment.selesai_menunggu') }}</h1>
    <p class="text-white/40 text-sm leading-relaxed mb-8">{{ __('payment.selesai_menunggu_sub') }}</p>

    <div x-show="lambat" style="display:none" class="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3 text-xs text-amber-300 leading-relaxed mb-5 text-left">
        {{ __('payment.selesai_lambat') }}
    </div>

    <div class="space-y-3">
        <a href="{{ route('payment.selesai', $order->gateway_order_id) }}"
           class="block w-full py-3 rounded-xl bg-white/5 hover:bg-white/10 text-sm text-white/70 transition">
            {{ __('payment.selesai_cek') }}
        </a>
        <a href="{{ route('trial.katalog') }}" class="block text-white/30 hover:text-white/50 text-xs transition">
            {{ __('payment.selesai_ke_katalog') }}
        </a>
    </div>

    <p class="text-white/20 text-xs mt-8">{{ __('payment.order_id') }}: <span class="font-mono">{{ $order->gateway_order_id }}</span></p>
</div>
@endsection
