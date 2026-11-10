@extends('layouts.app')

@section('title', __('panduan.title'))

@section('content')
@php
    $t = fn (string $k) => __('panduan.' . $k, $p);
    $h2 = 'font-semibold text-base mb-3 text-white';
    $card = 'bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 mb-5';
@endphp
<div class="max-w-2xl mx-auto px-4 py-12">

    <div class="text-center mb-8">
        <div class="text-4xl mb-3">📖</div>
        <h1 class="text-2xl font-bold mb-2">{{ $t('title') }}</h1>
        <p class="text-white/40 text-sm leading-relaxed">{{ $t('intro') }}</p>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">{{ $t('langkah_title') }}</h2>
        <ol class="space-y-2.5 text-sm text-white/60 leading-relaxed list-decimal pl-5">
            @foreach(__('panduan.langkah') as $l)<li>{{ $l }}</li>@endforeach
        </ol>
    </div>

    <div class="{{ $card }}" id="paket">
        <h2 class="{{ $h2 }}">{{ $t('paket_title') }}</h2>
        <div class="space-y-4">
            @foreach(__('panduan.paket') as $k)
            <div>
                <div class="text-sm font-medium text-purple-200">{{ __($k['judul'], $p) }}</div>
                <p class="text-xs text-white/50 leading-relaxed mt-1">{{ __($k['isi'], $p) }}</p>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-white/40 mt-4 leading-relaxed">{{ $t('paket_saran') }}</p>
    </div>

    <div class="{{ $card }}" id="jatah">
        <h2 class="{{ $h2 }}">{{ $t('jatah_title') }}</h2>
        <p class="text-sm text-white/60 leading-relaxed mb-4">{{ $t('jatah_intro') }}</p>
        <div class="text-xs text-white/40 uppercase tracking-wider mb-2">{{ $t('jatah_contoh_title') }}</div>
        <ol class="space-y-2 text-sm text-white/60 leading-relaxed list-decimal pl-5 mb-4">
            @foreach(__('panduan.jatah_contoh') as $c)<li>{{ __($c, $p) }}</li>@endforeach
        </ol>
        <p class="text-xs text-white/40 leading-relaxed">{{ $t('jatah_catatan') }}</p>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">{{ $t('hari_title') }}</h2>
        <p class="text-sm text-white/60 leading-relaxed">{{ $t('hari_isi') }}</p>
    </div>

    <div class="{{ $card }}" id="bayar">
        <h2 class="{{ $h2 }}">{{ $t('bayar_title') }}</h2>
        <ul class="space-y-2 text-sm text-white/60 leading-relaxed list-disc pl-5">
            @foreach(__('panduan.bayar') as $b)<li>{{ $b }}</li>@endforeach
        </ul>
    </div>

    <div class="{{ $card }}" id="akun">
        <h2 class="{{ $h2 }}">{{ $t('akun_title') }}</h2>
        <ul class="space-y-2 text-sm text-white/60 leading-relaxed list-disc pl-5">
            @foreach(__('panduan.akun') as $a)<li>{{ $a }}</li>@endforeach
        </ul>
    </div>

    <div class="{{ $card }}">
        <h2 class="{{ $h2 }}">{{ $t('faq_title') }}</h2>
        <div class="space-y-4">
            @foreach(__('panduan.faq') as $f)
            <div>
                <div class="text-sm font-medium text-white/80">{{ $f['q'] }}</div>
                <p class="text-xs text-white/50 leading-relaxed mt-1">{{ __($f['a'], $p) }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <p class="text-center text-xs text-white/25 leading-relaxed">{{ $t('disclaimer') }}</p>

    <p class="text-center mt-6">
        <a href="{{ route('trial.katalog') }}" class="text-white/30 hover:text-white/50 text-xs transition">{{ __('auth.pengaturan_kembali') }}</a>
    </p>
</div>
@endsection
