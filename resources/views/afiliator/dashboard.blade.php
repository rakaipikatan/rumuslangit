@extends('layouts.app')

@section('title', __('afiliator.dash_title'))

@php
    $rp = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');
    $kartu = 'bg-cosmic-800/60 border border-white/10 rounded-2xl p-5';
    $warna = ['ditahan' => 'text-amber-300', 'siap' => 'text-emerald-300', 'diproses' => 'text-sky-300', 'dibayar' => 'text-white/50'];
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">

    <div class="flex items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-xl font-bold">{{ __('afiliator.dash_title') }}</h1>
            <p class="text-white/40 text-sm">{{ __('afiliator.halo', ['nama' => $a->name]) }}</p>
        </div>
        <div class="flex items-center gap-3 text-xs">
            <a href="{{ route('afiliator.panduan') }}" class="text-white/50 hover:text-white underline">{{ __('afiliator.panduan') }}</a>
            <form method="POST" action="{{ route('afiliator.keluar') }}">@csrf
                <button class="text-white/40 hover:text-white/70 border border-white/10 rounded-lg px-3 py-1.5">{{ __('afiliator.keluar') }}</button></form>
        </div>
    </div>

    {{-- Link referral --}}
    <div class="{{ $kartu }} mb-4" x-data="{ ok: false }">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-2">{{ __('afiliator.link_title') }}</div>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ $link }}" id="refLink"
                   class="flex-1 min-w-0 bg-cosmic-700/50 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white/80">
            <button type="button"
                    @click="navigator.clipboard.writeText(document.getElementById('refLink').value).then(() => { ok = true; setTimeout(() => ok = false, 2000) })"
                    class="bg-purple-600 hover:bg-purple-500 rounded-xl px-4 text-sm font-medium transition whitespace-nowrap"
                    x-text="ok ? '{{ __('afiliator.link_tersalin') }}' : '{{ __('afiliator.link_salin') }}'"></button>
        </div>
        <p class="text-xs text-white/40 mt-2 leading-relaxed">{{ __('afiliator.link_info', ['persen' => $a->komisi_persen, 'bulan' => $bulan]) }}</p>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        @foreach([
            [__('afiliator.stat_mendaftar'), number_format($mendaftar)],
            [__('afiliator.stat_membayar'), number_format($membayar)],
            [__('afiliator.stat_total'), $rp($saldo['total'])],
            [__('afiliator.stat_dibayar'), $rp($saldo['dibayar'])],
        ] as [$label, $nilai])
        <div class="{{ $kartu }} !p-4">
            <div class="text-lg font-bold">{{ $nilai }}</div>
            <div class="text-[11px] text-white/40 leading-tight mt-1">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    {{-- Saldo & payout --}}
    <div class="{{ $kartu }} mb-4">
        <h2 class="font-semibold text-sm mb-3">{{ __('afiliator.saldo_title') }}</h2>
        <div class="grid sm:grid-cols-3 gap-3 mb-4">
            <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 p-3">
                <div class="text-lg font-bold text-emerald-300">{{ $rp($saldo['siap']) }}</div>
                <div class="text-xs text-white/70">{{ __('afiliator.saldo_siap') }}</div>
                <div class="text-[11px] text-white/40 mt-1 leading-snug">{{ __('afiliator.saldo_siap_ket', ['minimum' => number_format($minimum, 0, ',', '.')]) }}</div>
            </div>
            <div class="rounded-xl bg-amber-500/10 border border-amber-500/20 p-3">
                <div class="text-lg font-bold text-amber-300">{{ $rp($saldo['ditahan']) }}</div>
                <div class="text-xs text-white/70">{{ __('afiliator.saldo_ditahan') }}</div>
                <div class="text-[11px] text-white/40 mt-1 leading-snug">{{ __('afiliator.saldo_ditahan_ket', ['hari' => config('payment.affiliate_hold_days', 14)]) }}</div>
            </div>
            <div class="rounded-xl bg-sky-500/10 border border-sky-500/20 p-3">
                <div class="text-lg font-bold text-sky-300">{{ $rp($saldo['diproses']) }}</div>
                <div class="text-xs text-white/70">{{ __('afiliator.saldo_diproses') }}</div>
                <div class="text-[11px] text-white/40 mt-1 leading-snug">{{ __('afiliator.saldo_diproses_ket') }}</div>
            </div>
        </div>

        @if($saldo['siap'] >= $minimum)
            <p class="text-xs text-emerald-300/90 mb-2">{{ __('afiliator.payout_cukup', ['berikutnya' => $berikutnya]) }}</p>
        @else
            <p class="text-xs text-white/60 mb-2">{{ __('afiliator.payout_kurang', ['siap' => number_format($saldo['siap'], 0, ',', '.'), 'kurang' => number_format($minimum - $saldo['siap'], 0, ',', '.')]) }}</p>
        @endif
        <p class="text-xs text-white/40 leading-relaxed">{{ __('afiliator.payout_info', [
            'tanggal' => config('payment.affiliate_payout_day', 25), 'berikutnya' => $berikutnya, 'minimum' => number_format($minimum, 0, ',', '.'),
            'bank' => $a->bank_nama, 'rekening' => str_repeat('•', max(0, strlen($a->bank_rekening) - 4)) . substr($a->bank_rekening, -4), 'atas_nama' => $a->bank_atas_nama,
        ]) }} {{ __('afiliator.rekening_salah') }}</p>
    </div>

    {{-- Komisi terbaru --}}
    <div class="{{ $kartu }} mb-4">
        <h2 class="font-semibold text-sm mb-3">{{ __('afiliator.komisi_title') }}</h2>
        @if($komisi->isEmpty())
            <p class="text-sm text-white/40">{{ __('afiliator.komisi_kosong') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="text-white/40 text-left">
                        <tr><th class="pb-2 font-medium">{{ __('afiliator.col_tanggal') }}</th><th class="pb-2 font-medium">{{ __('afiliator.col_pengguna') }}</th><th class="pb-2 font-medium">{{ __('afiliator.col_produk') }}</th><th class="pb-2 font-medium text-right">{{ __('afiliator.col_komisi') }}</th><th class="pb-2 font-medium text-right">{{ __('afiliator.col_status') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                    @foreach($komisi as $k)
                        <tr>
                            <td class="py-2 text-white/60">{{ $k['tanggal'] }}</td>
                            <td class="py-2 text-white/60">{{ $k['pengguna'] }}</td>
                            <td class="py-2 text-white/60">{{ $k['produk'] }}</td>
                            <td class="py-2 text-right font-medium">{{ $rp($k['jumlah']) }}</td>
                            <td class="py-2 text-right {{ $warna[$k['status']] }}">{{ __('afiliator.st_' . $k['status']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-white/30 mt-3">{{ __('afiliator.privasi') }}</p>
        @endif
    </div>

    {{-- Riwayat payout --}}
    <div class="{{ $kartu }}">
        <h2 class="font-semibold text-sm mb-3">{{ __('afiliator.payout_riwayat') }}</h2>
        @forelse($payouts as $w)
            <div class="flex items-center justify-between text-xs py-2 border-b border-white/5 last:border-0">
                <span class="text-white/60">{{ $w->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</span>
                <span class="font-medium">{{ $rp($w->jumlah) }}</span>
                <span class="{{ $w->status === 'paid' ? 'text-emerald-300' : 'text-sky-300' }}">{{ $w->status === 'paid' ? __('afiliator.payout_paid') . ' ' . $w->paid_at->timezone('Asia/Jakarta')->format('d/m') : __('afiliator.payout_pending') }}</span>
            </div>
        @empty
            <p class="text-sm text-white/40">{{ __('afiliator.payout_kosong') }}</p>
        @endforelse
    </div>
</div>
@endsection
