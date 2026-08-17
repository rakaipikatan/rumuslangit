@extends('layouts.app')

@section('title', __('laporan.mimpi.title'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">

    <div class="flex items-center gap-2 text-xs text-white/30 mb-8">
        <a href="{{ route('trial.katalog') }}" class="hover:text-white/50 transition">{{ __('laporan.mimpi.kembali') }}</a>
        <span>/</span>
        <span>{{ $fitur['hub'] }}</span>
        <span>/</span>
        <span>{{ $fitur['nama'] }}</span>
    </div>

    <div class="mb-8">
        <div class="inline-flex items-center gap-2 bg-cosmic-600/40 border border-purple-500/20 rounded-full px-3 py-1 text-xs text-purple-300 mb-4">
            {{ $fitur['hub'] }} · {{ __('laporan.mimpi.freemium') }}
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold mb-2">
            {{ __('laporan.mimpi.heading') }}
        </h1>
        <p class="text-white/40 text-sm leading-relaxed">{{ $fitur['deskripsi'] }}</p>
    </div>

    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 sm:p-8">
        <form action="{{ route('laporan.proses', $featureId) }}" method="POST"
              x-data="{ teks: '', loading: false }"
              @submit="loading = true">
            @csrf

            <label class="block text-sm font-medium text-white/70 mb-3">
                {{ $fitur['pertanyaan_terbuka'] }}
            </label>

            <textarea
                name="mimpi"
                x-model="teks"
                rows="4"
                maxlength="500"
                placeholder="{{ __('laporan.mimpi.placeholder') }}"
                class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 text-sm resize-none focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition"
                required
            ></textarea>

            <div class="flex justify-between text-xs text-white/25 mt-1.5 mb-5">
                <span>{{ __('laporan.mimpi.minimal_char') }}</span>
                <span x-text="teks.length + ' / 500'"></span>
            </div>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 mb-4 text-red-300 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <button
                type="submit"
                :disabled="teks.length < 5 || loading"
                :class="teks.length < 5 || loading ? 'opacity-50 cursor-not-allowed' : 'hover:from-purple-500 hover:to-violet-500 shadow-lg shadow-purple-900/40'"
                class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200"
            >
                <span x-show="!loading" class="flex items-center justify-center gap-2">{{ __('laporan.mimpi.submit') }}</span>
                <span x-show="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    {{ __('laporan.mimpi.submitting') }}
                </span>
            </button>

            <p class="text-center text-white/25 text-xs mt-3">
                {{ __('laporan.mimpi.footer_note') }}
            </p>
        </form>
    </div>
</div>
@endsection
