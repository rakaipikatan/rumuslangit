@extends('admin.layout')
@section('title', 'Laporan Analisis')

@section('content')

{{-- Stats per fitur --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    @foreach ($statsPerFitur as $sf)
    <a href="{{ route('admin.reports', ['fitur' => $sf['id']]) }}"
       class="bg-cosmic-800/60 border rounded-xl p-3 text-center transition hover:border-purple-500/30
              {{ request('fitur') == $sf['id'] ? 'border-purple-500/40 bg-purple-500/10' : 'border-white/8' }}">
        <div class="text-xl font-bold text-white">{{ $sf['total'] }}</div>
        <div class="text-xs text-white/40 mt-0.5 leading-tight">{{ Str::limit($sf['nama'], 20) }}</div>
    </a>
    @endforeach
</div>

{{-- Filter --}}
<div class="flex gap-3 mb-5 flex-wrap">
    <form method="GET" class="flex gap-2">
        <select name="fitur" class="bg-cosmic-800/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none [color-scheme:dark]">
            <option value="">Semua Fitur</option>
            @foreach ($fiturList as $id => $f)
            <option value="{{ $id }}" {{ request('fitur') == $id ? 'selected':'' }}>{{ $f['nama'] }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm text-white transition">Filter</button>
        <a href="{{ route('admin.reports') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-sm text-white/50 transition">Reset</a>
    </form>
</div>

<div class="bg-cosmic-800/60 border border-white/8 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-white/40 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">User</th>
                <th class="px-5 py-3 text-left">Fitur</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Dibuat</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse ($reports as $r)
            <tr class="hover:bg-white/2 transition">
                <td class="px-5 py-3">
                    <div class="text-white/80">{{ $r->user?->name ?? 'User #'.$r->user_id }}</div>
                    <div class="text-xs text-white/30">{{ $r->user?->email ?: '—' }}</div>
                </td>
                <td class="px-5 py-3">
                    @php $fiturNama = \App\Data\FiturData::cari($r->feature_id, 'id'); @endphp
                    <span class="badge-pill bg-purple-500/15 text-purple-300 border border-purple-500/20">
                        {{ $fiturNama['icon'] ?? '' }} {{ $fiturNama['nama'] ?? "Fitur #{$r->feature_id}" }}
                    </span>
                </td>
                <td class="px-5 py-3">
                    @if ($r->response_text)
                    <span class="badge-pill bg-emerald-500/15 text-emerald-300 border border-emerald-500/20">✓ Selesai</span>
                    @else
                    <span class="badge-pill bg-amber-500/15 text-amber-300 border border-amber-500/20">⏳ Proses</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-white/30 text-xs">
                    {{ $r->cached_at ? \Carbon\Carbon::parse($r->cached_at)->format('d/m/Y H:i') : '—' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-5 py-10 text-center text-white/30">Belum ada laporan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $reports->links() }}</div>
@endsection
