@extends('admin.layout')
@section('title', 'Payout Afiliator')

@section('content')
@php $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.'); @endphp

<div class="mb-5 text-sm text-white/50 leading-relaxed max-w-3xl">
    Payout dibuat <b>otomatis</b> tiap tanggal {{ config('payment.affiliate_payout_day', 25) }} (09:00 WIB) untuk afiliator yang saldo "siap dibayar"-nya ≥ <b>{{ $rp($minimum) }}</b>.
    Tugas Anda cukup: unduh CSV → transfer (bisa transfer massal di internet banking) → klik <b>Tandai semua sudah ditransfer</b>.
    Afiliator melihat statusnya di dashboard mereka.
</div>

<div class="flex flex-wrap items-center gap-3 mb-5">
    <div class="text-sm">Menunggu transfer: <span class="text-amber-400 font-semibold">{{ $rp($total) }}</span> ({{ $pending->count() }} payout)</div>
    @if($pending->isNotEmpty())
        <a href="{{ route('admin.payout.csv') }}" class="px-4 py-2 bg-white/10 hover:bg-white/15 rounded-xl text-sm transition">⬇ Unduh CSV</a>
        <form method="POST" action="{{ route('admin.payout.semua') }}" onsubmit="return confirm('Tandai SEMUA payout menunggu sebagai sudah ditransfer? Pastikan transfer sudah dilakukan.')">
            @csrf <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm text-white transition">✓ Tandai semua sudah ditransfer</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.payout.buat') }}" class="ml-auto">
        @csrf <button class="px-3 py-2 border border-white/10 hover:border-white/20 rounded-xl text-xs text-white/50 transition" title="Biasanya tidak perlu — dijalankan otomatis tiap bulan">Buat payout sekarang</button>
    </form>
</div>

<div class="border border-white/10 rounded-2xl overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-white/5 text-white/50 text-xs text-left"><tr>
            <th class="px-4 py-3">Afiliator</th><th class="px-4 py-3">Rekening</th><th class="px-4 py-3 text-right">Jumlah</th><th class="px-4 py-3">Dibuat</th><th class="px-4 py-3"></th>
        </tr></thead>
        <tbody class="divide-y divide-white/5">
        @forelse($pending as $w)
            <tr>
                <td class="px-4 py-3">{{ $w->affiliate?->name }}</td>
                <td class="px-4 py-3 text-white/60 text-xs">{{ $w->bank_nama }} · {{ $w->bank_rekening }}<br>a.n. {{ $w->bank_atas_nama }}</td>
                <td class="px-4 py-3 text-right font-semibold">{{ $rp($w->jumlah) }}</td>
                <td class="px-4 py-3 text-white/50 text-xs">{{ $w->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-right">
                    <form method="POST" action="{{ route('admin.payout.dibayar', $w->id) }}">@csrf
                        <button class="text-xs px-3 py-1.5 bg-emerald-600/80 hover:bg-emerald-500 rounded-lg">Sudah ditransfer</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-white/40 text-sm">Tidak ada payout yang menunggu.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<h2 class="font-semibold text-sm mb-3 text-white/70">Riwayat payout (30 terakhir)</h2>
<div class="border border-white/10 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <tbody class="divide-y divide-white/5">
        @forelse($riwayat as $w)
            <tr>
                <td class="px-4 py-2.5">{{ $w->affiliate?->name }}</td>
                <td class="px-4 py-2.5 text-right">{{ $rp($w->jumlah) }}</td>
                <td class="px-4 py-2.5 text-white/50 text-xs text-right">{{ $w->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td class="px-4 py-6 text-center text-white/40 text-sm">Belum ada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
