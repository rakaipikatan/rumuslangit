@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')

{{-- Stat Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['Total Users',      $stats['total_users'],      'bg-purple-500/10 text-purple-300', '👥'],
        ['Email Verified',   $stats['users_verified'],   'bg-cyan-500/10 text-cyan-300',     '✉️'],
        ['Subscriber Aktif', $stats['subscribers_aktif'],'bg-emerald-500/10 text-emerald-300','⭐'],
        ['Laporan Dibuat',   $stats['total_reports'],    'bg-amber-500/10 text-amber-300',   '📄'],
    ] as [$label, $val, $cls, $icon])
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-white/40 uppercase tracking-wider">{{ $label }}</span>
            <div class="w-8 h-8 rounded-xl {{ $cls }} flex items-center justify-center text-base">{{ $icon }}</div>
        </div>
        <div class="text-2xl font-bold text-white">{{ number_format($val) }}</div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach ([
        ['Revenue',         'Rp '.number_format($stats['revenue_settled'],0,',','.'), 'bg-green-500/10 text-green-300','💰'],
        ['Total Orders',    $stats['total_orders'],    'bg-blue-500/10 text-blue-300',  '📦'],
        ['Laporan Hari Ini',$stats['reports_hari_ini'],'bg-violet-500/10 text-violet-300','📈'],
        ['Pending Jobs',    $stats['pending_jobs'],    'bg-orange-500/10 text-orange-300','⏳'],
    ] as [$label, $val, $cls, $icon])
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-white/40 uppercase tracking-wider">{{ $label }}</span>
            <div class="w-8 h-8 rounded-xl {{ $cls }} flex items-center justify-center text-base">{{ $icon }}</div>
        </div>
        <div class="text-2xl font-bold text-white">{{ $val }}</div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Feature Usage --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl p-5">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-4">Top Fitur Digunakan</h2>
        <div class="space-y-2.5">
            @foreach ($featureUsage as $fu)
            @php $pct = $stats['total_reports'] > 0 ? round($fu['total'] / $stats['total_reports'] * 100) : 0; @endphp
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-white/70 truncate pr-2">{{ $fu['nama'] }}</span>
                    <span class="text-white/40 flex-shrink-0">{{ $fu['total'] }}</span>
                </div>
                <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-cyan-500 rounded-full"
                         style="width:{{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
            @if ($featureUsage->isEmpty())
            <p class="text-white/30 text-xs">Belum ada laporan.</p>
            @endif
        </div>
    </div>

    {{-- Recent Users --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider">User Terbaru</h2>
            <a href="{{ route('admin.users') }}" class="text-xs text-purple-400 hover:text-purple-300">Lihat semua →</a>
        </div>
        <div class="space-y-2.5">
            @forelse ($recentUsers as $u)
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-full bg-purple-500/20 flex items-center justify-center text-xs text-purple-300 flex-shrink-0">
                    {{ strtoupper(substr($u->name,0,1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white/80 truncate">{{ $u->name }}</p>
                    <p class="text-xs text-white/30 truncate">{{ $u->email ?: 'Belum verifikasi' }}</p>
                </div>
                @if ($u->subscription_status === 'active')
                <span class="badge-pill bg-emerald-500/15 text-emerald-300 border border-emerald-500/20">⭐</span>
                @endif
            </div>
            @empty
            <p class="text-white/30 text-xs">Belum ada user.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-cosmic-800/60 border border-white/8 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wider">Order Terbaru</h2>
            <a href="{{ route('admin.orders') }}" class="text-xs text-purple-400 hover:text-purple-300">Lihat semua →</a>
        </div>
        <div class="space-y-2.5">
            @forelse ($recentOrders as $o)
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-sm text-white/80 truncate">{{ $o->user?->name ?? 'User #'.$o->user_id }}</p>
                    <p class="text-xs text-white/30">Rp {{ number_format($o->amount,0,',','.') }}</p>
                </div>
                <span class="badge-pill flex-shrink-0 {{ $o->status === 'settlement' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20' : ($o->status === 'pending' ? 'bg-amber-500/15 text-amber-300 border border-amber-500/20' : 'bg-red-500/15 text-red-300 border border-red-500/20') }}">
                    {{ $o->status }}
                </span>
            </div>
            @empty
            <p class="text-white/30 text-xs">Belum ada order.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
