@extends('admin.layout')
@section('title', 'Afiliator')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="text-sm text-white/50">
        Total komisi belum dibayar: <span class="text-amber-400 font-semibold">Rp {{ number_format($affiliates->sum('total_unpaid'), 0, ',', '.') }}</span>
        &nbsp;·&nbsp;
        Sudah dibayar: <span class="text-emerald-400 font-semibold">Rp {{ number_format($affiliates->sum('total_paid'), 0, ',', '.') }}</span>
    </div>
    <button onclick="document.getElementById('modal-buat-affiliate').classList.remove('hidden')"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm text-white transition whitespace-nowrap">
        + Tambah Afiliator
    </button>
</div>

{{-- Modal Buat Afiliator --}}
<div id="modal-buat-affiliate" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">
    <div class="bg-cosmic-800 border border-white/10 rounded-2xl p-6 w-full max-w-md shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-bold text-white">Tambah Afiliator</h2>
            <button onclick="document.getElementById('modal-buat-affiliate').classList.add('hidden')"
                    class="text-white/30 hover:text-white/60 text-xl leading-none">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.affiliates.buat') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Nama</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition">
            </div>
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Kode Referral</label>
                <input type="text" name="referral_code" value="{{ old('referral_code') }}" placeholder="mis. BUDI10" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition uppercase placeholder-white/20">
                <p class="text-xs text-white/25 mt-1">Dipakai di link: rumuslangit.biz.id/?ref=KODE</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition">
                </div>
                <div>
                    <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">No. HP</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition">
                </div>
            </div>
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Komisi (%)</label>
                <input type="number" name="komisi_persen" value="{{ old('komisi_persen', 20) }}" min="1" max="100" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition">
            </div>
            <div class="border-t border-white/5 pt-4">
                <p class="text-xs text-white/40 uppercase tracking-wider mb-3">Rekening untuk transfer manual (tanggal 25)</p>
                <div class="space-y-3">
                    <input type="text" name="bank_nama" value="{{ old('bank_nama') }}" placeholder="Nama Bank (mis. BCA)"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition placeholder-white/20">
                    <input type="text" name="bank_rekening" value="{{ old('bank_rekening') }}" placeholder="Nomor Rekening"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition placeholder-white/20">
                    <input type="text" name="bank_atas_nama" value="{{ old('bank_atas_nama') }}" placeholder="Atas Nama"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition placeholder-white/20">
                </div>
            </div>
            @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-300 text-xs">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
            @endif
            <div class="flex gap-3 pt-2">
                <button type="button"
                    onclick="document.getElementById('modal-buat-affiliate').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-sm text-white/50 transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm text-white font-semibold transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="bg-cosmic-800/60 border border-white/8 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-white/40 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Afiliator</th>
                <th class="px-5 py-3 text-left">Kode</th>
                <th class="px-5 py-3 text-left">Komisi</th>
                <th class="px-5 py-3 text-left">User Referral</th>
                <th class="px-5 py-3 text-left">Belum Dibayar</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse ($affiliates as $a)
            <tr class="hover:bg-white/2 transition">
                <td class="px-5 py-3">
                    <div class="font-medium text-white/85">{{ $a->name }}</div>
                    <div class="text-xs text-white/30">{{ $a->email ?: $a->phone ?: '—' }}</div>
                </td>
                <td class="px-5 py-3">
                    <span class="font-mono text-xs text-purple-300 bg-purple-500/10 border border-purple-500/20 rounded-lg px-2 py-1">{{ $a->referral_code }}</span>
                </td>
                <td class="px-5 py-3 text-white/70">{{ $a->komisi_persen }}%</td>
                <td class="px-5 py-3 text-white/50">{{ $a->users_count }}</td>
                <td class="px-5 py-3">
                    <span class="{{ $a->total_unpaid > 0 ? 'text-amber-400 font-semibold' : 'text-white/30' }}">
                        Rp {{ number_format($a->total_unpaid ?? 0, 0, ',', '.') }}
                    </span>
                </td>
                <td class="px-5 py-3">
                    <span class="badge-pill {{ $a->status === 'active' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20' : 'bg-white/5 text-white/40 border border-white/10' }}">
                        {{ $a->status }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.affiliates.detail', $a->id) }}" class="text-xs text-purple-300/70 hover:text-purple-300 transition">Detail</a>
                        <form method="POST" action="{{ route('admin.affiliates.toggle', $a->id) }}">
                            @csrf
                            <button type="submit" class="text-xs text-white/40 hover:text-white/70 transition">
                                {{ $a->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-white/30">Belum ada afiliator.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
