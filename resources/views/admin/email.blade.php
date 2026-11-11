@extends('admin.layout')
@section('title', 'Riwayat Email')

@section('content')
@php
    $rp  = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $wib = fn ($t) => $t ? $t->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '—';
    $card = 'bg-cosmic-800/60 border border-white/8 rounded-2xl p-6';
    $h  = 'text-xs font-semibold text-white/50 uppercase tracking-wider mb-4';
    $in = 'bg-cosmic-700/50 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500/60 [color-scheme:dark]';
    $qs = request()->except('page');
@endphp

{{-- Filter --}}
<form method="GET" action="{{ route('admin.email') }}" class="{{ $card }} mb-6 grid grid-cols-2 lg:grid-cols-6 gap-3 items-end">
    <div><label class="block text-xs text-white/40 mb-1">Dari (WIB)</label><input type="date" name="dari" value="{{ $dariStr }}" class="{{ $in }} w-full"></div>
    <div><label class="block text-xs text-white/40 mb-1">Sampai (WIB)</label><input type="date" name="sampai" value="{{ $sampaiStr }}" class="{{ $in }} w-full"></div>
    <div><label class="block text-xs text-white/40 mb-1">Jenis</label>
        <select name="jenis" class="{{ $in }} w-full"><option value="">Semua</option>
            @foreach($jenisList as $k => $l)<option value="{{ $k }}" @selected(request('jenis') === $k)>{{ $l }}</option>@endforeach
        </select></div>
    <div><label class="block text-xs text-white/40 mb-1">Status</label>
        <select name="status" class="{{ $in }} w-full"><option value="">Semua</option>
            <option value="terkirim" @selected(request('status') === 'terkirim')>Terkirim</option>
            <option value="gagal" @selected(request('status') === 'gagal')>Gagal</option>
        </select></div>
    <div><label class="block text-xs text-white/40 mb-1">Cari penerima/pengguna</label><input type="text" name="cari" value="{{ request('cari') }}" placeholder="email atau nama" class="{{ $in }} w-full"></div>
    <div class="flex gap-2">
        <button class="flex-1 px-3 py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm text-white transition">Terapkan</button>
        <a href="{{ route('admin.email.csv', $qs) }}" class="px-3 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm text-white/80 transition" title="Unduh CSV sesuai filter">CSV</a>
    </div>
</form>

{{-- Ringkasan --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Email terkirim', number_format($ringkasan['terkirim']), 'text-emerald-300'],
        ['Gagal', number_format($ringkasan['gagal']), $ringkasan['gagal'] > 0 ? 'text-amber-300' : 'text-white'],
        ['Perkiraan biaya', $rp($ringkasan['biaya']), 'text-white'],
        ['Harga per email', $rp($biayaEmail), 'text-white/70'],
    ] as [$l, $v, $c])
    <div class="stat-card"><div class="text-xs text-white/40 uppercase tracking-wider mb-2">{{ $l }}</div><div class="text-2xl font-bold {{ $c }}">{{ $v }}</div></div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Rekonsiliasi --}}
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">Rekonsiliasi dengan SumoPod</h2>
        <p class="text-xs text-white/40 leading-relaxed mb-3">Isi jumlah email yang terpakai menurut dashboard SumoPod untuk periode yang sama (kredit awal dikurangi sisa kredit) untuk melihat selisihnya dengan catatan di sini.</p>
        <form method="GET" action="{{ route('admin.email') }}" class="flex flex-wrap items-end gap-2">
            @foreach(request()->except(['sumopod', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="number" min="0" name="sumopod" value="{{ $sumopod }}" placeholder="jumlah menurut SumoPod" class="{{ $in }} flex-1 min-w-[10rem]">
            <button class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm text-white/80 transition">Bandingkan</button>
        </form>
        @if($selisih !== null)
        <div class="mt-4 text-sm">
            <div class="flex justify-between"><span class="text-white/40">Catatan di sini</span><span class="text-white/80">{{ number_format($ringkasan['terkirim']) }}</span></div>
            <div class="flex justify-between"><span class="text-white/40">Menurut SumoPod</span><span class="text-white/80">{{ number_format($sumopod) }}</span></div>
            <div class="flex justify-between border-t border-white/10 mt-2 pt-2"><span class="text-white/50">Selisih</span>
                <span class="{{ $selisih === 0 ? 'text-emerald-300' : 'text-amber-300' }} font-semibold">{{ $selisih > 0 ? '+' : '' }}{{ number_format($selisih) }} ({{ $rp(abs($selisih) * $biayaEmail) }})</span></div>
            @if($selisih !== 0)
            <p class="text-xs text-white/40 mt-3 leading-relaxed">
                {{ $selisih > 0 ? 'SumoPod mencatat LEBIH BANYAK: kemungkinan ada email dikirim di luar aplikasi ini, atau email yang dikirim sebelum riwayat ini ada.' : 'Catatan di sini LEBIH BANYAK: kemungkinan email diterima SMTP tetapi tidak ditagih, atau periode/zona waktu berbeda.' }}
            </p>
            @endif
        </div>
        @endif
    </div>

    {{-- Per jenis --}}
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">Per jenis email</h2>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-white/30 uppercase tracking-wider"><th class="py-1.5">Jenis</th><th class="text-right">Terkirim</th><th class="text-right">Gagal</th><th class="text-right">Biaya</th></tr></thead>
            <tbody class="divide-y divide-white/5">
            @forelse($perJenis as $j)
                <tr><td class="py-1.5 text-white/80">{{ $jenisList[$j->jenis] ?? $j->jenis }}</td><td class="text-right text-white/70">{{ number_format($j->terkirim) }}</td><td class="text-right {{ $j->gagal > 0 ? 'text-amber-300' : 'text-white/30' }}">{{ number_format($j->gagal) }}</td><td class="text-right text-white/50">{{ $rp($j->biaya) }}</td></tr>
            @empty
                <tr><td colspan="4" class="py-3 text-white/30">Belum ada email pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-4 pt-3 border-t border-white/5 text-xs text-white/40 space-y-1">
            <div class="flex justify-between"><span>Email uji (tidak menempel ke pengguna)</span><span class="text-white/60">{{ number_format($tanpaPengguna['uji']) }}</span></div>
            <div class="flex justify-between"><span>Penerima bukan pengguna terdaftar</span><span class="text-white/60">{{ number_format($tanpaPengguna['lainnya']) }}</span></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Per pengguna --}}
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">Pemakaian per pengguna (15 teratas)</h2>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-white/30 uppercase tracking-wider"><th class="py-1.5">Pengguna</th><th class="text-right">Email</th><th class="text-right">Biaya</th></tr></thead>
            <tbody class="divide-y divide-white/5">
            @forelse($topPengguna as $t)
                @php $u = $namaPengguna[$t->user_id] ?? null; @endphp
                <tr>
                    <td class="py-1.5">@if($u)<a href="{{ route('admin.users.detail', $u->id) }}" class="text-white/80 hover:text-purple-300 transition">{{ $u->name }}</a><div class="text-xs text-white/30">{{ $u->email }}</div>@else<span class="text-white/30">pengguna terhapus</span>@endif</td>
                    <td class="text-right text-white/70">{{ number_format($t->jumlah) }}</td><td class="text-right text-white/50">{{ $rp($t->biaya) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-3 text-white/30">Belum ada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Per hari --}}
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">Per hari (WIB, 45 hari terakhir pada periode)</h2>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-white/30 uppercase tracking-wider"><th class="py-1.5">Tanggal</th><th class="text-right">Terkirim</th><th class="text-right">Gagal</th><th class="text-right">Biaya</th></tr></thead>
            <tbody class="divide-y divide-white/5">
            @forelse($perHari as $d)
                <tr><td class="py-1.5 text-white/80">{{ \Illuminate\Support\Carbon::parse($d->hari)->format('d/m/Y') }}</td><td class="text-right text-white/70">{{ number_format($d->terkirim) }}</td><td class="text-right {{ $d->gagal > 0 ? 'text-amber-300' : 'text-white/30' }}">{{ number_format($d->gagal) }}</td><td class="text-right text-white/50">{{ $rp($d->biaya) }}</td></tr>
            @empty
                <tr><td colspan="4" class="py-3 text-white/30">Belum ada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Daftar --}}
<div class="{{ $card }} overflow-x-auto">
    <h2 class="{{ $h }}">Riwayat ({{ number_format($daftar->total()) }})</h2>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs text-white/30 uppercase tracking-wider"><th class="py-2 pr-4">Waktu (WIB)</th><th class="pr-4">Jenis</th><th class="pr-4">Penerima</th><th class="pr-4">Pengguna</th><th class="pr-4">Status</th><th class="text-right">Biaya</th></tr></thead>
        <tbody class="divide-y divide-white/5">
        @forelse($daftar as $e)
            <tr>
                <td class="py-2 pr-4 text-white/40 text-xs">{{ $wib($e->created_at) }}</td>
                <td class="pr-4 text-white/80">{{ $jenisList[$e->jenis] ?? $e->jenis }}</td>
                <td class="pr-4 text-white/60 text-xs">{{ $e->email ?: '—' }}</td>
                <td class="pr-4 text-xs">@if($e->user)<a href="{{ route('admin.users.detail', $e->user_id) }}" class="text-purple-300/80 hover:text-purple-200">{{ $e->user->name }}</a>@else<span class="text-white/25">—</span>@endif</td>
                <td class="pr-4 text-xs {{ $e->status === 'terkirim' ? 'text-emerald-300' : 'text-amber-300' }}">{{ $e->status }}@if($e->pesan_error)<div class="text-white/30">{{ \Illuminate\Support\Str::limit($e->pesan_error, 90) }}</div>@endif</td>
                <td class="text-right text-white/50 text-xs">{{ $rp($e->biaya) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-6 text-center text-white/30">Belum ada email pada periode/filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $daftar->links() }}</div>
</div>
@endsection
