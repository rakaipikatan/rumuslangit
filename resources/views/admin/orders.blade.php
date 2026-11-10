@extends('admin.layout')
@section('title', 'Orders')

@section('content')

{{-- Revenue summary --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['settlement', 'Lunas',   'emerald'],
        ['pending',    'Pending', 'amber'],
        ['expire',     'Expire',  'red'],
        ['cancel',     'Cancel',  'red'],
    ] as [$st, $label, $color])
    @php $cnt = $orders->getCollection()->where('status',$st)->count(); @endphp
    <a href="{{ route('admin.orders', ['status'=>$st]) }}"
       class="bg-cosmic-800/60 border rounded-xl p-4 transition hover:border-{{ $color }}-500/30
              {{ request('status')===$st ? "border-{$color}-500/40 bg-{$color}-500/10" : 'border-white/8' }}">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-1">{{ $label }}</div>
        <div class="text-xl font-bold text-white">
            {{ \App\Models\Order::where('status',$st)->when(request('status'), fn($q)=>$q->where('status', request('status')))->count() }}
        </div>
    </a>
    @endforeach
</div>

<div class="flex items-center justify-between mb-5">
    <div class="text-sm text-white/50">
        Total revenue settled: <span class="text-emerald-400 font-semibold">Rp {{ number_format($revenue, 0, ',', '.') }}</span>
    </div>
    <form method="GET" class="flex gap-2">
        <select name="status" class="bg-cosmic-800/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none [color-scheme:dark]">
            <option value="">Semua Status</option>
            <option value="settlement" {{ request('status')==='settlement'?'selected':'' }}>Lunas (settlement)</option>
            <option value="pending"    {{ request('status')==='pending'?'selected':'' }}>Pending</option>
            <option value="expire"     {{ request('status')==='expire'?'selected':'' }}>Expire</option>
            <option value="cancel"     {{ request('status')==='cancel'?'selected':'' }}>Cancel</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm text-white transition">Filter</button>
        <a href="{{ route('admin.orders') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-sm text-white/50 transition">Reset</a>
    </form>
</div>

<div class="bg-cosmic-800/60 border border-white/8 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-white/40 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Order ID</th>
                <th class="px-5 py-3 text-left">User</th>
                <th class="px-5 py-3 text-left">Nominal</th>
                <th class="px-5 py-3 text-left">Pembayaran</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Tanggal</th>
                <th class="px-5 py-3 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse ($orders as $o)
            <tr class="hover:bg-white/2 transition">
                <td class="px-5 py-3 text-white/40 font-mono text-xs">
                    {{ $o->gateway_order_id ?? $o->id }}<div class="text-white/20">#{{ $o->id }}</div>
                </td>
                <td class="px-5 py-3">
                    @if($o->user)<a href="{{ route('admin.users.detail', $o->user_id) }}" class="text-white/80 hover:text-purple-300 transition">{{ $o->user->name }}</a>@else<div class="text-white/80">User #{{ $o->user_id }}</div>@endif
                    <div class="text-xs text-white/30">{{ $o->user?->email ?: '—' }}</div>
                </td>
                <td class="px-5 py-3 text-white/80">
                    Rp {{ number_format($o->amount, 0, ',', '.') }}
                    @php
                        $namaProduk = $o->feature_id === \App\Models\Order::SUBSCRIPTION_MONTHLY_FEATURE_ID ? 'Langganan Bulanan'
                            : ($o->feature_id === \App\Models\Order::SUBSCRIPTION_YEARLY_FEATURE_ID ? 'Langganan Tahunan'
                            : (\App\Data\FiturData::cari((int) $o->feature_id, 'id')['nama'] ?? 'Fitur #'.$o->feature_id));
                    @endphp
                    <div class="text-xs text-purple-300/70">{{ $namaProduk }}</div>
                </td>
                <td class="px-5 py-3 text-white/80">
                    @if($o->payment_method === 'komplimen')
                        <div class="text-xs text-cyan-300/80">GRATIS (admin)</div>
                        @if($o->catatan)<div class="text-xs text-white/30">{{ $o->catatan }}</div>@endif
                    @elseif($o->payment_method === 'qris')
                        <div>QRIS</div>
                        @if($o->payment_id)<div class="text-xs text-white/30 font-mono" title="{{ $o->payment_id }}">{{ \Illuminate\Support\Str::limit($o->payment_id, 13, '…') }}</div>@endif
                        @if($o->status === 'pending' && $o->payment_expires_at)
                            <div class="text-xs {{ $o->payment_expires_at->isPast() ? 'text-red-300/70' : 'text-amber-300/70' }}">{{ $o->payment_expires_at->isPast() ? 'lewat masa berlaku' : 'berlaku s/d ' . $o->payment_expires_at->timezone('Asia/Jakarta')->format('d/m H:i') . ' WIB' }}</div>
                        @endif
                    @elseif($o->transfer_amount)
                        <div>Rp {{ number_format($o->transfer_amount, 0, ',', '.') }}</div>
                        <div class="text-xs text-white/30">{{ strtoupper((string) $o->bank_tujuan) }} · kode {{ $o->unique_code }} (transfer lama)</div>
                    @else
                        <span class="text-white/20">—</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    @php
                        $badgeCls = match($o->status) {
                            'settlement' => 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20',
                            'pending'    => 'bg-amber-500/15 text-amber-300 border border-amber-500/20',
                            default      => 'bg-red-500/15 text-red-300 border border-red-500/20',
                        };
                    @endphp
                    <span class="badge-pill {{ $badgeCls }}">{{ $o->status }}</span>
                </td>
                <td class="px-5 py-3 text-white/30 text-xs">
                    {{ $o->created_at ? $o->created_at->format('d/m/Y H:i') : '—' }}
                </td>
                <td class="px-5 py-3">
                    @if($o->status === 'pending')
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.orders.konfirmasi', $o->id) }}"
                              onsubmit="return confirm('Konfirmasi order #{{ $o->id }} sebagai lunas?');">
                            @csrf
                            <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg bg-emerald-500/15 text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/25 transition">Konfirmasi</button>
                        </form>
                        <form method="POST" action="{{ route('admin.orders.tolak', $o->id) }}"
                              onsubmit="return confirm('Tolak/batalkan order #{{ $o->id }}?');">
                            @csrf
                            <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg bg-red-500/15 text-red-300 border border-red-500/20 hover:bg-red-500/25 transition">Tolak</button>
                        </form>
                    </div>
                    @elseif($o->isSettled() && $o->amount > 0 && $o->payment_method !== 'komplimen' && $o->user?->email)
                        <form method="POST" action="{{ route('admin.orders.kirim-bukti', $o->id) }}"
                              onsubmit="return confirm('Kirim ulang bukti bayar ke {{ addslashes($o->user->email) }}?');">
                            @csrf
                            <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg bg-purple-500/15 text-purple-300 border border-purple-500/20 hover:bg-purple-500/25 transition">Kirim ulang bukti</button>
                        </form>
                    @else
                        <span class="text-white/20 text-xs">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-white/30">Belum ada order.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
