<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Dashboard') — Admin Rumus Langit</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>tailwind.config={theme:{extend:{colors:{cosmic:{'900':'#060614','800':'#0d0d2b','700':'#141438','600':'#1c1c50'}}}}}</script>
<style type="text/tailwindcss">
    .nav-link { @apply flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-white/50 border-l-2 border-transparent hover:text-white hover:bg-white/5 transition-all; }
    .nav-link.active { @apply text-white bg-purple-500/15 border-l-2 border-purple-400; }
    .nav-icon { @apply flex items-center justify-center w-5 h-5 text-base shrink-0; }
    .nav-section-label { @apply px-3.5 pt-4 pb-1.5 text-xs font-semibold uppercase tracking-wider text-white/25; }
    .stat-card { @apply bg-cosmic-800/60 border border-white/10 rounded-2xl p-5; }
    .badge-pill { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium; }
</style>
</head>
<body class="bg-cosmic-900 text-white min-h-screen flex" style="font-family:'Segoe UI',system-ui,sans-serif;">

{{-- Sidebar --}}
<aside class="w-60 flex-shrink-0 bg-cosmic-800/50 border-r border-white/5 flex flex-col fixed inset-y-0 left-0 z-40">
    <div class="px-5 py-5 border-b border-white/5 flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-violet-700 flex items-center justify-center text-sm shrink-0 shadow-lg shadow-purple-900/30">✦</div>
        <div class="min-w-0">
            <div class="text-sm font-bold text-white leading-tight truncate">Rumus Langit</div>
            <div class="text-[11px] text-white/30 leading-tight">Admin Panel</div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-2 overflow-y-auto">
        <div class="nav-section-label">Menu</div>
        <div class="space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="{{ route('admin.users') }}"
               class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <span class="nav-icon">👥</span> Users
            </a>
            <a href="{{ route('admin.reports') }}"
               class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                <span class="nav-icon">📄</span> Laporan
            </a>
            <a href="{{ route('admin.orders') }}"
               class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                <span class="nav-icon">💳</span> Orders
            </a>
            <a href="{{ route('admin.affiliates') }}"
               class="nav-link {{ request()->routeIs('admin.affiliates*') ? 'active' : '' }}">
                <span class="nav-icon">🤝</span> Afiliator
            </a>
        </div>

        <div class="nav-section-label">Lainnya</div>
        <div class="space-y-1">
            <a href="{{ route('admin.system') }}"
               class="nav-link {{ request()->routeIs('admin.system*') ? 'active' : '' }}">
                <span class="nav-icon">⚙️</span> Sistem
            </a>
            <a href="{{ route('trial.index') }}" target="_blank" class="nav-link">
                <span class="nav-icon">🌐</span> Lihat App
                <span class="ml-auto text-white/20 text-xs">↗</span>
            </a>
        </div>
    </nav>

    <div class="px-3 py-3 border-t border-white/5 space-y-2">
        <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-white/[0.03]">
            <div class="w-7 h-7 rounded-full bg-purple-500/20 text-purple-300 flex items-center justify-center text-xs font-semibold shrink-0">
                {{ strtoupper(substr(session('admin_username', 'A'), 0, 1)) }}
            </div>
            <div class="min-w-0">
                <div class="text-xs font-medium text-white/80 truncate">{{ session('admin_username') }}</div>
                <div class="text-[10px] text-white/30">Administrator</div>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="nav-link w-full text-red-400/70 hover:text-red-300 hover:bg-red-500/10">
                <span class="nav-icon">🚪</span> Logout
            </button>
        </form>
    </div>
</aside>

{{-- Main --}}
<main class="flex-1 ml-60 min-h-screen flex flex-col">
    {{-- Top bar --}}
    <header class="sticky top-0 z-30 bg-cosmic-900/80 backdrop-blur border-b border-white/5 px-6 py-3.5 flex items-center justify-between">
        <h1 class="text-base font-semibold text-white">@yield('title', 'Dashboard')</h1>
        <div class="flex items-center gap-2 text-xs text-white/30">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
    </header>

    {{-- Flash messages --}}
    @if (session('success'))
    <div class="mx-6 mt-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 text-emerald-300 text-sm">
        ✓ {{ session('success') }}
    </div>
    @endif

    <div class="flex-1 px-6 py-6">
        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>
