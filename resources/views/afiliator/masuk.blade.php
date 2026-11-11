@extends('layouts.app')

@section('title', __('afiliator.masuk_title'))

@section('content')
<div class="max-w-md mx-auto px-4 py-14">
    <div class="text-center mb-8">
        <div class="text-4xl mb-3">🤝</div>
        <h1 class="text-2xl font-bold mb-2">{{ __('afiliator.masuk_title') }}</h1>
        <p class="text-white/40 text-sm">{{ __('afiliator.masuk_sub') }}</p>
    </div>

    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6">
        @if(session('kode_terkirim') && $emailKode)
            <p class="text-xs text-emerald-300/90 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-3 py-2 mb-4 leading-relaxed">{{ __('afiliator.kode_terkirim', ['email' => $emailKode]) }}</p>
            <form method="POST" action="{{ route('afiliator.kode.verifikasi') }}" class="space-y-3">
                @csrf
                <label class="block text-xs font-medium text-white/60 uppercase tracking-wider">{{ __('afiliator.kode') }}</label>
                <input type="text" name="kode_otp" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required autofocus
                       class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-center text-xl tracking-[0.4em] focus:outline-none focus:border-purple-500/60">
                @error('kode_otp')<p class="text-xs text-red-300">{{ $message }}</p>@enderror
                <button class="w-full bg-purple-600 hover:bg-purple-500 rounded-xl py-3 font-semibold text-sm transition">{{ __('afiliator.masuk') }}</button>
            </form>
            <p class="text-center mt-4"><a href="{{ route('afiliator.masuk') }}?ganti=1" class="text-xs text-white/40 hover:text-white/70 underline">{{ __('afiliator.ganti_email') }}</a></p>
        @else
            <form method="POST" action="{{ route('afiliator.kode.kirim') }}" class="space-y-3">
                @csrf
                <label class="block text-xs font-medium text-white/60 uppercase tracking-wider">{{ __('afiliator.email') }}</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60">
                @error('email')<p class="text-xs text-red-300">{{ $message }}</p>@enderror
                <button class="w-full bg-purple-600 hover:bg-purple-500 rounded-xl py-3 font-semibold text-sm transition">{{ __('afiliator.kirim_kode') }}</button>
            </form>
        @endif
    </div>

    <p class="text-center text-xs text-white/40 mt-5">{{ __('afiliator.belum_daftar') }}
        <a href="{{ route('afiliasi.index') }}" class="text-purple-300 underline">{{ __('afiliator.daftar_link') }}</a></p>
</div>
@endsection
