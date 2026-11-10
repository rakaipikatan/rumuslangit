@extends('layouts.app')

@section('title', __('auth.pengaturan_title'))

@section('content')
<div class="max-w-md mx-auto px-4 py-14">

    <div class="text-center mb-8">
        <div class="text-4xl mb-3">⚙</div>
        <h1 class="text-2xl font-bold mb-1">{{ __('auth.pengaturan_title') }}</h1>
        <p class="text-white/40 text-sm">{{ $user->name }}</p>
    </div>

    @if(session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 mb-6 flex items-center gap-3 text-emerald-300 text-sm">
        <span class="text-lg">✓</span><span>{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 space-y-6">

        <div>
            <div class="text-xs text-white/40 uppercase tracking-wider mb-1">{{ __('auth.pengaturan_email') }}</div>
            <div class="text-sm text-white">{{ $user->email }}</div>
        </div>

        <div class="h-px bg-white/5"></div>

        <form method="POST" action="{{ route('auth.password') }}" class="space-y-4">
            @csrf

            <div>
                <div class="text-xs text-white/40 uppercase tracking-wider mb-1">{{ __('auth.pengaturan_password_judul') }}</div>
                <p class="text-xs text-white/40 leading-relaxed">
                    {{ filled($user->password) ? __('auth.pengaturan_password_info_ada') : __('auth.pengaturan_password_info_belum') }}
                </p>
            </div>

            @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-300 text-xs">{{ $errors->first() }}</div>
            @endif

            @if(filled($user->password))
            <input type="password" name="current_password" required autocomplete="current-password" placeholder="{{ __('auth.password_lama') }}"
                   class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition">
            @endif

            <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="{{ __('auth.password_baru') }}"
                   class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition">
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="{{ __('auth.password_ulangi') }}"
                   class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition">

            <button type="submit"
                    class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40">
                {{ __('auth.password_simpan') }}
            </button>
        </form>
    </div>

    <p class="text-center mt-6">
        <a href="{{ route('trial.katalog') }}" class="text-white/30 hover:text-white/50 text-xs transition">{{ __('auth.pengaturan_kembali') }}</a>
    </p>
</div>
@endsection
