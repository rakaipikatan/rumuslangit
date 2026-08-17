@extends('admin.layout')
@section('title', 'Afiliator — ' . $affiliate->name)

@section('content')

<a href="{{ route('admin.affiliates') }}" class="text-xs text-white/30 hover:text-white/50 transition mb-4 inline-block">← Kembali ke daftar afiliator</a>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-card">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-1">Belum Dibayar</div>
        <div class="text-xl font-bold text-amber-400">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-1">Sudah Dibayar</div>
        <div class="text-xl font-bold text-emerald-400">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
    </div>
    <div class="stat-card">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-1">Komisi Rate</div>
        <div class="text-xl font-bold text-white">{{ $affiliate->komisi_persen }}%</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    {{-- Info afiliator + rekening --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-5 lg:col-span-1">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-3">Info Afiliator</div>
        <div class="space-y-2.5 text-sm">
            <div class="flex justify-between"><span class="text-white/40">Nama</span><span class="text-white/80">{{ $affiliate->name }}</span></div>
            <div class="flex justify-between"><span class="text-white/40">Kode</span><span class="font-mono text-purple-300">{{ $affiliate->referral_code }}</span></div>
            <div class="flex justify-between"><span class="text-white/40">Email</span><span class="text-white/80">{{ $affiliate->email ?: '—' }}</span></div>
            <div class="flex justify-between"><span class="text-white/40">No. HP</span><span class="text-white/80">{{ $affiliate->phone ?: '—' }}</span></div>
        </div>
        <div class="h-px bg-white/5 my-4"></div>
        <div class="text-xs text-white/40 uppercase tracking-wider mb-3">Rekening Pembayaran</div>
        <div class="space-y-2.5 text-sm">
            <div class="flex justify-between"><span class="text-white/40">Bank</span><span class="text-white/80">{{ $affiliate->bank_nama ?: '—' }}</span></div>
            <div class="flex justify-between"><span class="text-white/40">No. Rekening</span><span class="font-mono text-white/80">{{ $affiliate->bank_rekening ?: '—' }}</span></div>
            <div class="flex justify-between"><span class="text-white/40">Atas Nama</span><span class="text-white/80">{{ $affiliate->bank_atas_nama ?: '—' }}</span></div>
        </div>

        @if ($totalUnpaid > 0)
        <form method="POST" action="{{ route('admin.affiliates.lunas', $affiliate->id) }}" class="mt-5"
              onsubmit="return confirm('Tandai semua komisi belum dibayar ({{ number_format($totalUnpaid,0,',','.') }}) sebagai LUNAS? Lakukan ini hanya setelah transfer manual selesai dikirim.')">
            @csrf
            <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm font-semibold text-white transition">
                ✓ Tandai Lunas (Rp {{ number_format($totalUnpaid, 0, ',', '.') }})
            </button>
        </form>
        @endif
    </div>

    {{-- Riwayat komisi --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl overflow-hidden lg:col-span-2">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5 text-xs text-white/40 uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Nominal Order</th>
                    <th class="px-4 py-3 text-left">Komisi</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse ($komisi as $k)
                <tr class="hover:bg-white/2 transition">
                    <td class="px-4 py-3 text-white/40 font-mono text-xs">{{ $k->order?->gateway_order_id ?? '#'.$k->order_id }}</td>
                    <td class="px-4 py-3 text-white/70">{{ $k->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-white/60">Rp {{ number_format($k->order_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-white/85 font-medium">Rp {{ number_format($k->komisi_amount, 0, ',', '.') }} <span class="text-white/30 text-xs">({{ $k->komisi_persen }}%)</span></td>
                    <td class="px-4 py-3">
                        <span class="badge-pill {{ $k->status === 'paid' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20' : 'bg-amber-500/15 text-amber-300 border border-amber-500/20' }}">
                            {{ $k->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-white/30 text-xs">{{ $k->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-white/30">Belum ada komisi tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $komisi->links() }}</div>
    </div>
</div>
@endsection
