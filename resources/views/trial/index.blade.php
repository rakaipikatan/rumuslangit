@extends('layouts.app')

@section('title', __('trial.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">

    {{-- Hero --}}
    <div class="text-center mb-10">
        <img src="{{ asset('images/logo.png') }}" alt="Rumus Langit" class="h-56 w-auto mx-auto mb-4 drop-shadow-[0_0_40px_rgba(212,175,55,0.3)]">
        <h1 class="text-3xl sm:text-4xl font-bold leading-tight mb-4">
            {{ __('trial.hero_title_1') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-cyan-400">{{ __('trial.hero_title_2') }}</span><br>
            {{ __('trial.hero_title_3') }}
        </h1>
        <p class="text-white/50 text-sm leading-relaxed max-w-md mx-auto">
            {{ __('trial.hero_desc') }}
        </p>
    </div>

    {{-- Pain point --}}
    <div class="bg-cosmic-800/40 border border-white/5 rounded-2xl p-5 sm:p-6 mb-8">
        <h2 class="text-sm font-semibold text-white/70 mb-3">{{ __('trial.pain.title') }}</h2>
        <ul class="space-y-2 mb-4">
            @foreach (__('trial.pain.items') as $item)
            <li class="flex items-start gap-2 text-xs text-white/50 leading-relaxed">
                <span class="text-purple-400 mt-0.5 flex-shrink-0">●</span>{{ $item }}
            </li>
            @endforeach
        </ul>
        <p class="text-xs text-white/40 leading-relaxed border-t border-white/5 pt-3">{{ __('trial.pain.closing') }}</p>
    </div>

    {{-- Penjelasan: apa yang sedang diakses --}}
    <div class="bg-cosmic-800/40 border border-white/5 rounded-2xl p-5 sm:p-6 mb-8">
        <h2 class="text-sm font-semibold text-white/70 mb-1">{{ __('trial.explainer.title') }}</h2>
        <p class="text-xs text-white/40 leading-relaxed mb-4">{{ __('trial.explainer.desc') }}</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach (__('trial.explainer.items') as ['label' => $label, 'desc' => $d])
            <div class="bg-cosmic-700/30 border border-white/5 rounded-xl p-3">
                <div class="text-xs font-semibold text-purple-300 mb-1">{{ $label }}</div>
                <div class="text-xs text-white/40 leading-relaxed">{{ $d }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Perbandingan --}}
    <div class="bg-cosmic-800/40 border border-white/5 rounded-2xl p-5 sm:p-6 mb-8">
        <h2 class="text-sm font-semibold text-white/70 mb-4 text-center">{{ __('trial.banding.title') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-cosmic-700/30 border border-white/5 rounded-xl p-4">
                <div class="text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">{{ __('trial.banding.generik_label') }}</div>
                <ul class="space-y-2">
                    @foreach (__('trial.banding.generik_items') as $item)
                    <li class="flex items-start gap-2 text-xs text-white/40 leading-relaxed">
                        <span class="text-white/20 mt-0.5 flex-shrink-0">✗</span>{{ $item }}
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-4">
                <div class="text-xs font-semibold text-purple-300 uppercase tracking-wider mb-2">{{ __('trial.banding.rumus_label') }}</div>
                <ul class="space-y-2">
                    @foreach (__('trial.banding.rumus_items') as $item)
                    <li class="flex items-start gap-2 text-xs text-white/70 leading-relaxed">
                        <span class="text-emerald-400 mt-0.5 flex-shrink-0">✓</span>{{ $item }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- Cara Kerja --}}
    <div class="bg-cosmic-800/40 border border-white/5 rounded-2xl p-5 sm:p-6 mb-8">
        <h2 class="text-sm font-semibold text-white/70 mb-4">{{ __('trial.cara_kerja.title') }}</h2>
        <div class="space-y-4">
            @foreach (__('trial.cara_kerja.steps') as ['no' => $no, 'title' => $stepTitle, 'desc' => $stepDesc])
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-7 h-7 rounded-full bg-purple-500/15 border border-purple-500/30 text-purple-300 text-xs font-bold flex items-center justify-center">{{ $no }}</div>
                <div>
                    <div class="text-sm font-medium text-white/80">{{ $stepTitle }}</div>
                    <div class="text-xs text-white/40 leading-relaxed mt-0.5">{{ $stepDesc }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Preview Semua 12 Laporan --}}
    <div class="bg-cosmic-800/40 border border-white/5 rounded-2xl p-5 sm:p-6 mb-8">
        <h2 class="text-sm font-semibold text-white/70 mb-1">{{ __('trial.preview.title') }}</h2>
        <p class="text-xs text-white/40 leading-relaxed mb-4">{{ __('trial.preview.desc') }}</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach (\App\Data\FiturData::semua() as $f)
            <div class="flex items-start gap-3 bg-cosmic-700/30 border border-white/5 rounded-xl p-3">
                <span class="text-xl flex-shrink-0">{{ $f['icon'] }}</span>
                <div>
                    <div class="text-xs font-semibold text-white/70">{{ $f['nama'] }}</div>
                    <div class="text-xs text-white/40 leading-relaxed mt-0.5">{{ $f['deskripsi'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-white/30 text-center mt-4">{{ __('trial.preview.cta_note') }}</p>
    </div>

    {{-- Trust badges --}}
    <div class="grid grid-cols-3 gap-4 text-center mb-8">
        @foreach (__('trial.badges') as ['icon' => $icon, 'title' => $title, 'sub' => $sub])
        <div class="bg-cosmic-800/40 border border-white/5 rounded-xl p-3">
            <div class="text-xl mb-1">{{ $icon }}</div>
            <div class="text-xs font-medium text-white/70">{{ $title }}</div>
            <div class="text-xs text-white/30 mt-0.5">{{ $sub }}</div>
        </div>
        @endforeach
    </div>

    {{-- CTA besar ke halaman form --}}
    <div class="text-center">
        <a href="{{ route('trial.mulai') }}"
           class="inline-block w-full sm:w-auto px-10 py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40 active:scale-[0.98]">
            {{ __('trial.cta_mulai_besar') }}
        </a>
        <p class="text-center text-white/25 text-xs mt-3">{{ __('trial.cta_mulai_sub') }}</p>
    </div>

    {{-- Disclaimer --}}
    <p class="mt-6 text-center text-xs text-white/25 leading-relaxed">
        {{ __('trial.disclaimer') }} <a href="https://www.primbon.com" target="_blank" rel="noopener" class="underline underline-offset-2 hover:text-white/40 transition">primbon.com</a>
    </p>
</div>
@endsection
