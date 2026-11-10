@extends('layouts.app')

@section('title', __('afiliasi.title'))

@section('content')
@php
    $in = 'w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition';
    $lb = 'block text-xs font-medium text-white/50 mb-1.5 uppercase tracking-wider';
@endphp
<div class="max-w-xl mx-auto px-4 py-14">

    <div class="text-center mb-8">
        <div class="text-4xl mb-3">🤝</div>
        <h1 class="text-2xl font-bold mb-2">{{ __('afiliasi.heading') }}</h1>
        <p class="text-white/40 text-sm leading-relaxed">{{ __('afiliasi.subtitle') }}</p>
    </div>

    {{-- Komisi --}}
    <div class="bg-gradient-to-br from-purple-900/40 to-cyan-900/20 border border-purple-500/30 rounded-2xl p-6 text-center mb-6">
        <div class="text-xs text-white/40 uppercase tracking-wider mb-1">{{ __('afiliasi.komisi_label') }}</div>
        <div class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-cyan-300">{{ $komisi }}%</div>
        <p class="text-white/50 text-xs mt-2">{{ __('afiliasi.komisi_desc') }}</p>
    </div>

    {{-- Cara kerja --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 mb-6">
        <h2 class="font-semibold text-sm mb-4">{{ __('afiliasi.cara_title') }}</h2>
        <ol class="space-y-3 text-sm text-white/60">
            @foreach([1,2,3,4] as $n)
            <li class="flex gap-3"><span class="text-purple-300 font-semibold">{{ $n }}.</span><span>{{ __('afiliasi.cara_' . $n) }}</span></li>
            @endforeach
        </ol>
    </div>

    {{-- Syarat & Ketentuan --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 mb-6">
        <h2 class="font-semibold text-sm mb-4">{{ __('afiliasi.syarat_title') }}</h2>
        @include('afiliasi._syarat', ['komisi' => $komisi])
    </div>

    @if(session('terkirim'))
    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-6 text-center">
        <div class="text-3xl mb-2">✓</div>
        <h2 class="font-semibold text-emerald-300 mb-1">{{ __('afiliasi.terkirim_title') }}</h2>
        <p class="text-white/50 text-sm leading-relaxed">{{ __('afiliasi.terkirim_desc') }}</p>
    </div>
    @else
    <form method="POST" action="{{ route('afiliasi.daftar') }}" class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 space-y-4">
        @csrf
        <h2 class="font-semibold text-sm">{{ __('afiliasi.form_title') }}</h2>

        @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-300 text-xs">{{ $errors->first() }}</div>
        @endif

        {{-- Honeypot: disembunyikan dari manusia, bot biasanya mengisinya --}}
        <div style="position:absolute;left:-9999px" aria-hidden="true">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div><label class="{{ $lb }}">{{ __('afiliasi.nama') }}</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" class="{{ $in }}"></div>
        <div><label class="{{ $lb }}">{{ __('afiliasi.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="{{ $in }}"></div>
        <div><label class="{{ $lb }}">{{ __('afiliasi.hp') }}</label>
            <input type="text" name="phone" value="{{ old('phone') }}" required maxlength="30" inputmode="tel" class="{{ $in }}"></div>
        <div><label class="{{ $lb }}">{{ __('afiliasi.promosi') }}</label>
            <textarea name="promosi" rows="3" maxlength="1000" placeholder="{{ __('afiliasi.promosi_ph') }}" class="{{ $in }}">{{ old('promosi') }}</textarea></div>

        <div class="border-t border-white/5 pt-4 space-y-4">
            <h3 class="text-xs text-white/50 uppercase tracking-wider">{{ __('afiliasi.rekening_title') }}</h3>
            <div><label class="{{ $lb }}">{{ __('afiliasi.bank') }}</label>
                <input type="text" name="bank_nama" value="{{ old('bank_nama') }}" required maxlength="30" placeholder="{{ __('afiliasi.bank_ph') }}" class="{{ $in }}"></div>
            <div><label class="{{ $lb }}">{{ __('afiliasi.no_rek') }}</label>
                <input type="text" name="bank_rekening" value="{{ old('bank_rekening') }}" required maxlength="50" inputmode="numeric" class="{{ $in }}"></div>
            <div><label class="{{ $lb }}">{{ __('afiliasi.atas_nama') }}</label>
                <input type="text" name="bank_atas_nama" value="{{ old('bank_atas_nama') }}" required maxlength="100" class="{{ $in }}"></div>
        </div>

        <label class="flex items-start gap-3 text-xs text-white/50 leading-relaxed cursor-pointer">
            <input type="checkbox" name="setuju" value="1" required class="mt-0.5 accent-purple-500" {{ old('setuju') ? 'checked' : '' }}>
            <span>{{ __('afiliasi.setuju') }}</span>
        </label>

        <button type="submit"
                class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40">
            {{ __('afiliasi.kirim') }}
        </button>
    </form>
    @endif

    <p class="text-center mt-6">
        <a href="{{ route('trial.index') }}" class="text-white/30 hover:text-white/50 text-xs transition">{{ __('afiliasi.kembali') }}</a>
    </p>
</div>
@endsection
