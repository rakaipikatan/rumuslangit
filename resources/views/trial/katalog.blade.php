@extends('layouts.app')

@section('title', __('hasil.katalog.title', ['name' => $user->name]))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">

    {{-- Header --}}
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/30 rounded-full px-4 py-1.5 text-xs text-emerald-300 mb-4">
            {{ __('hasil.katalog.akses_terbuka', ['name' => $user->name]) }}
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold mb-2">
            {{ __('hasil.katalog.pilih') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-cyan-400">{{ __('hasil.katalog.analisis') }}</span> {{ __('hasil.katalog.anda') }}
        </h1>
        <p class="text-white/40 text-sm max-w-md mx-auto">
            {{ __('hasil.katalog.desc') }}
            @if($user->isSubscriber())
                <span class="text-emerald-400">{{ __('hasil.katalog.subscriber_unlocked') }}</span>
            @else
                {{ __('hasil.katalog.mulai_dari') }}
            @endif
        </p>
    </div>

    {{-- Penjelasan kategori --}}
    <div class="bg-cosmic-800/40 border border-white/5 rounded-2xl p-5 mb-8">
        <p class="text-xs text-white/40 leading-relaxed text-center">{{ __('hasil.katalog.explainer') }}</p>
    </div>

    @php
        $hubs = [
            'Wealth Hub'          => ['icon' => '💰', 'color' => 'amber'],
            'Social Hub'          => ['icon' => '❤️', 'color' => 'pink'],
            'Personal Wellness Hub' => ['icon' => '✨', 'color' => 'purple'],
            'Traffic Driver'      => ['icon' => '🌙', 'color' => 'cyan'],
        ];
        $hubColors = [
            'amber'  => ['border' => 'border-amber-500/20',  'badge' => 'bg-amber-500/10 text-amber-300 border-amber-500/20'],
            'pink'   => ['border' => 'border-pink-500/20',   'badge' => 'bg-pink-500/10 text-pink-300 border-pink-500/20'],
            'purple' => ['border' => 'border-purple-500/20', 'badge' => 'bg-purple-500/10 text-purple-300 border-purple-500/20'],
            'cyan'   => ['border' => 'border-cyan-500/20',   'badge' => 'bg-cyan-500/10 text-cyan-300 border-cyan-500/20'],
        ];
    @endphp

    @foreach ($hubs as $hubNama => $hubMeta)
    @php
        $fiturHub = collect($fiturAll)->filter(fn($f) => $f['hub'] === $hubNama);
        $color    = $hubColors[$hubMeta['color']];
    @endphp
    @if ($fiturHub->count())
    <div class="mb-8">
        {{-- Hub header --}}
        <div class="flex items-center gap-2 mb-4">
            <span class="text-lg">{{ $hubMeta['icon'] }}</span>
            <h2 class="font-semibold text-white/70 text-sm uppercase tracking-wider">{{ $hubNama }}</h2>
            <div class="flex-1 h-px bg-white/5"></div>
        </div>

        {{-- Fitur cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach ($fiturHub as $id => $fitur)
            @php
                $locked = !empty($fitur['subscriber_only']) && !$user->isSubscriber();
            @endphp
            <a href="{{ $locked ? '#' : route('laporan.form', $id) }}"
               class="group relative flex items-start gap-4 bg-cosmic-800/60 hover:bg-cosmic-700/60 border {{ $color['border'] }} hover:border-opacity-60 rounded-2xl p-5 transition-all duration-200 {{ $locked ? 'opacity-60 cursor-not-allowed' : '' }}">

                {{-- Icon --}}
                <div class="text-2xl mt-0.5 flex-shrink-0">{{ $fitur['icon'] }}</div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h3 class="font-semibold text-sm text-white group-hover:text-white transition leading-tight">
                            {{ $fitur['nama'] }}
                        </h3>
                        @if ($locked)
                            <span class="flex-shrink-0 text-xs bg-white/5 text-white/30 border border-white/10 rounded-full px-2 py-0.5">
                                {{ __('hasil.katalog.locked_subscriber') }}
                            </span>
                        @elseif ($user->isSubscriber())
                            <span class="flex-shrink-0 text-xs {{ $color['badge'] }} border rounded-full px-2 py-0.5">
                                {{ __('hasil.katalog.bebas') }}
                            </span>
                        @elseif (!empty($fitur['freemium']))
                            <span class="flex-shrink-0 text-xs bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 rounded-full px-2 py-0.5">
                                {{ __('hasil.katalog.gratis') }}
                            </span>
                        @else
                            <span class="flex-shrink-0 text-xs text-white/50 font-medium">
                                Rp {{ number_format($fitur['harga'], 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-white/40 leading-relaxed line-clamp-2">{{ $fitur['deskripsi'] }}</p>
                </div>

                {{-- Arrow --}}
                @if (!$locked)
                <div class="flex-shrink-0 text-white/20 group-hover:text-white/50 transition self-center">→</div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach

    {{-- Langganan CTA --}}
    @if (!$user->isSubscriber())
    <div class="mt-4 relative bg-gradient-to-br from-purple-900/40 to-cyan-900/20 border border-purple-500/30 rounded-2xl p-6 text-center overflow-hidden">
        <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 50% 0%,rgba(124,58,237,.12) 0%,transparent 60%)"></div>
        <div class="relative">
            <div class="text-2xl mb-2">⭐</div>
            <h3 class="font-bold text-base mb-1">{{ __('hasil.katalog.langganan_title') }}</h3>
            <p class="text-white/40 text-xs mb-4">{{ __('hasil.katalog.langganan_desc') }}</p>
            <a href="{{ route('payment.konfirmasi', 'langganan-bulanan') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all">
                {{ __('hasil.katalog.langganan_cta') }}
            </a>
        </div>
    </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ route('trial.hasil') }}" class="text-white/25 hover:text-white/40 text-xs transition">
            {{ __('hasil.katalog.kembali') }}
        </a>
    </div>
</div>
@endsection
