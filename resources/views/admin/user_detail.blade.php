@extends('admin.layout')
@section('title', 'Detail Pengguna')

@section('content')
@php
    $produk = fn ($fid) => $fid === \App\Models\Order::SUBSCRIPTION_MONTHLY_FEATURE_ID ? 'Langganan Bulanan'
        : ($fid === \App\Models\Order::SUBSCRIPTION_YEARLY_FEATURE_ID ? 'Langganan Tahunan' : ($fitur[$fid]['nama'] ?? "Fitur #{$fid}"));
    $wib = fn ($t) => $t ? $t->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '—';
    $card = 'bg-cosmic-800/60 border border-white/8 rounded-2xl p-6';
    $h = 'text-xs font-semibold text-white/50 uppercase tracking-wider mb-4';
    $in = 'bg-cosmic-700/50 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-purple-500/60 [color-scheme:dark]';
@endphp

<a href="{{ route('admin.users') }}" class="text-xs text-white/40 hover:text-white/70 transition">← Semua pengguna</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4 mb-6">
    {{-- Profil --}}
    <div class="{{ $card }} lg:col-span-1">
        <h2 class="{{ $h }}">Profil</h2>
        <div class="text-lg font-semibold text-white">{{ $user->name }} <span class="text-white/30 text-sm font-normal">#{{ $user->id }}</span></div>
        <div class="text-sm text-white/60 mt-1">{{ $user->email ?: '— (belum ada email)' }}</div>
        <div class="text-xs mt-1 {{ $user->email_verified_at ? 'text-emerald-400/80' : 'text-amber-400/80' }}">
            {{ $user->email_verified_at ? '✓ Terverifikasi ' . $wib($user->email_verified_at) : '⚠ Belum verifikasi email' }}
        </div>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-white/40">Lahir</dt><dd class="text-white/80">{{ $user->dob?->format('d/m/Y') }} {{ $user->birth_hour !== null ? '· jam ' . $user->birth_hour : '' }}</dd></div>
            <div class="flex justify-between"><dt class="text-white/40">Tempat</dt><dd class="text-white/80 text-right">{{ $user->kota ?: '—' }}{{ $user->province ? ', ' . $user->province : '' }}</dd></div>
            <div class="flex justify-between"><dt class="text-white/40">Daftar</dt><dd class="text-white/80">{{ $wib($user->created_at) }}</dd></div>
            <div class="flex justify-between"><dt class="text-white/40">Status</dt><dd class="text-white/80">{{ $user->subscription_status ?: 'free' }}{{ $user->isSubscriber() ? ' (aktif)' : '' }}</dd></div>
            <div class="flex justify-between"><dt class="text-white/40">Password</dt><dd class="text-white/80">{{ filled($user->password) ? 'sudah dibuat' : 'belum (login kode email)' }}</dd></div>
            <div class="flex justify-between"><dt class="text-white/40">Dibawa afiliator</dt>
                <dd class="text-white/80">
                    @if($user->affiliate)<a href="{{ route('admin.affiliates.detail', $user->affiliate->id) }}" class="text-purple-300 hover:text-purple-200">{{ $user->affiliate->name }} ({{ $user->affiliate->referral_code }})</a>@else — @endif
                </dd></div>
        </dl>
    </div>

    {{-- Beri akses --}}
    <div class="{{ $card }} lg:col-span-2">
        <h2 class="{{ $h }}">Beri akses gratis</h2>
        <p class="text-xs text-white/40 mb-4 leading-relaxed">Untuk kompensasi, promosi, atau uji. Dicatat sebagai order bernominal Rp0 (tidak masuk pendapatan, tanpa komisi afiliasi, tanpa email bukti bayar) lengkap dengan catatan.</p>

        <form method="POST" action="{{ route('admin.users.beri-jatah', $user->id) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end mb-5">
            @csrf
            <div class="sm:col-span-5">
                <label class="block text-xs text-white/40 mb-1">Laporan</label>
                <select name="feature_id" required class="{{ $in }} w-full">
                    @foreach($fiturBerbayar as $fid => $f)<option value="{{ $fid }}">{{ $f['nama'] }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs text-white/40 mb-1">Paket (×{{ config('payment.single_generations', 3) }} jatah)</label>
                <input type="number" name="paket" value="1" min="1" max="10" required class="{{ $in }} w-full">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-xs text-white/40 mb-1">Catatan</label>
                <input type="text" name="catatan" maxlength="200" placeholder="mis. kompensasi keluhan" class="{{ $in }} w-full">
            </div>
            <div class="sm:col-span-2">
                <button class="w-full px-3 py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm text-white transition">Beri jatah</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.users.beri-langganan', $user->id) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end"
              onsubmit="return confirm('Beri langganan gratis ke {{ addslashes($user->name) }}?')">
            @csrf
            <div class="sm:col-span-5">
                <label class="block text-xs text-white/40 mb-1">Langganan gratis</label>
                <select name="bulan" required class="{{ $in }} w-full">
                    @foreach([1 => '1 bulan', 3 => '3 bulan', 6 => '6 bulan', 12 => '12 bulan'] as $b => $l)<option value="{{ $b }}">{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-5">
                <label class="block text-xs text-white/40 mb-1">Catatan</label>
                <input type="text" name="catatan" maxlength="200" placeholder="mis. hadiah / uji coba" class="{{ $in }} w-full">
            </div>
            <div class="sm:col-span-2">
                <button class="w-full px-3 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm text-white transition">Beri langganan</button>
            </div>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">Jatah per laporan</h2>
        @forelse($kuota as $k)
        <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0 text-sm">
            <span class="text-white/80">{{ $k['nama'] }}</span>
            <span class="text-white/50">terpakai {{ $k['terpakai'] }} / {{ $k['total'] }} · <span class="{{ $k['sisa'] > 0 ? 'text-emerald-300' : 'text-amber-300' }}">sisa {{ $k['sisa'] }}</span></span>
        </div>
        @empty
        <p class="text-sm text-white/30">Belum ada pembelian laporan satuan.</p>
        @endforelse
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h }}">Langganan</h2>
        @forelse($user->subscriptions as $sub)
        <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0 text-sm">
            <span class="text-white/80">{{ $sub->plan === 'yearly' ? 'Tahunan' : 'Bulanan' }}</span>
            <span class="text-white/50">{{ $wib($sub->starts_at) }} → {{ $wib($sub->ends_at) }}
                @if($sub->ends_at && $sub->ends_at->isFuture())<span class="text-emerald-300">· aktif</span>@endif</span>
        </div>
        @empty
        <p class="text-sm text-white/30">Tidak ada riwayat langganan.</p>
        @endforelse
    </div>
</div>

<div class="{{ $card }} mb-6">
    <h2 class="{{ $h }}">Email yang dikirim ke pengguna ini</h2>
    <div class="text-sm text-white/70 mb-3">
        <span class="text-white font-semibold">{{ $emailStat['jumlah'] }}</span> terkirim · biaya ±<span class="text-white font-semibold">Rp {{ number_format($emailStat['biaya'], 0, ',', '.') }}</span>
        @if($emailStat['gagal'] > 0)<span class="text-amber-300"> · {{ $emailStat['gagal'] }} gagal</span>@endif
        @if($emailStat['perJenis']->isNotEmpty())
        <span class="text-white/40"> — {{ $emailStat['perJenis']->map(fn ($x) => (\App\Models\EmailLog::JENIS[$x->jenis] ?? $x->jenis) . ' ' . $x->n)->implode(', ') }}</span>
        @endif
    </div>
    @forelse($emailStat['terbaru'] as $e)
    <div class="flex items-center justify-between py-1.5 border-b border-white/5 last:border-0 text-xs">
        <span class="text-white/70">{{ \App\Models\EmailLog::JENIS[$e->jenis] ?? $e->jenis }}</span>
        <span class="text-white/30">{{ $wib($e->created_at) }}</span>
        <span class="{{ $e->status === 'terkirim' ? 'text-emerald-300' : 'text-amber-300' }}">{{ $e->status }}{{ $e->pesan_error ? ' — ' . \Illuminate\Support\Str::limit($e->pesan_error, 60) : '' }}</span>
    </div>
    @empty
    <p class="text-sm text-white/30">Belum ada email tercatat untuk pengguna ini.</p>
    @endforelse
    <a href="{{ route('admin.email', ['cari' => $user->email, 'dari' => '2020-01-01']) }}" class="inline-block mt-3 text-xs text-purple-300/80 hover:text-purple-200">Lihat semua di riwayat email →</a>
</div>

<div class="{{ $card }} mb-6 overflow-x-auto">
    <h2 class="{{ $h }}">Order ({{ $user->orders->count() }})</h2>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs text-white/30 uppercase tracking-wider"><th class="py-2 pr-4">No. Order</th><th class="pr-4">Produk</th><th class="pr-4">Nominal</th><th class="pr-4">Metode</th><th class="pr-4">Status</th><th class="pr-4">Tanggal</th><th></th></tr></thead>
        <tbody class="divide-y divide-white/5">
        @forelse($user->orders as $o)
            <tr>
                <td class="py-2 pr-4 font-mono text-xs text-white/50">{{ $o->gateway_order_id }}</td>
                <td class="pr-4 text-white/80">{{ $produk($o->feature_id) }}@if($o->catatan)<div class="text-xs text-white/30">{{ $o->catatan }}</div>@endif</td>
                <td class="pr-4 text-white/80">Rp {{ number_format($o->amount, 0, ',', '.') }}</td>
                <td class="pr-4 text-white/50">{{ $o->payment_method === 'komplimen' ? 'GRATIS (admin)' : strtoupper((string) $o->payment_method) }}</td>
                <td class="pr-4"><span class="text-xs {{ $o->status === 'settlement' ? 'text-emerald-300' : ($o->status === 'pending' ? 'text-amber-300' : 'text-red-300') }}">{{ $o->status }}</span></td>
                <td class="pr-4 text-white/30 text-xs">{{ $wib($o->created_at) }}</td>
                <td class="text-right">
                    @if($o->isSettled() && $o->amount > 0 && $o->payment_method !== 'komplimen' && $user->email)
                    <form method="POST" action="{{ route('admin.orders.kirim-bukti', $o->id) }}" onsubmit="return confirm('Kirim ulang bukti bayar ke {{ addslashes($user->email) }}?')">
                        @csrf<button class="text-xs text-purple-300/80 hover:text-purple-200 transition">Kirim ulang bukti</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="py-4 text-white/30">Belum ada order.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="{{ $card }} overflow-x-auto">
    <h2 class="{{ $h }}">Laporan terakhir (maks. 15)</h2>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs text-white/30 uppercase tracking-wider"><th class="py-2 pr-4">Laporan</th><th class="pr-4">Bahasa</th><th class="pr-4">Dibuat (WIB)</th><th class="pr-4">Memakai jatah</th><th>Status</th></tr></thead>
        <tbody class="divide-y divide-white/5">
        @forelse($laporan as $r)
            <tr>
                <td class="py-2 pr-4 text-white/80">{{ $r->feature_id === 0 ? 'Profil karakter (trial)' : ($fitur[$r->feature_id]['nama'] ?? "Fitur #{$r->feature_id}") }}</td>
                <td class="pr-4 text-white/50">{{ strtoupper($r->locale ?? 'id') }}</td>
                <td class="pr-4 text-white/30 text-xs">{{ $wib($r->cached_at) }}</td>
                <td class="pr-4 text-white/50">{{ $r->consumed_quota ? 'ya' : 'tidak' }}</td>
                <td class="text-xs {{ $r->response_text ? 'text-emerald-300' : 'text-amber-300' }}">{{ $r->response_text ? 'selesai' : 'belum selesai' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-4 text-white/30">Belum ada laporan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
