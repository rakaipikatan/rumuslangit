@extends('layouts.app')

@section('title', __('afiliator.pan_title'))

@section('content')
@php
    $h2 = 'font-semibold text-base mb-3 text-white';
    $card = 'bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 mb-5';
    $ol = 'space-y-2.5 text-sm text-white/60 leading-relaxed list-decimal pl-5';
    $ul = 'space-y-2.5 text-sm text-white/60 leading-relaxed list-disc pl-5';
@endphp
<div class="max-w-2xl mx-auto px-4 py-12">
    <a href="{{ route('afiliator.dashboard') }}" class="text-xs text-white/40 hover:text-white/70">{{ __('afiliator.pan_kembali') }}</a>
    <div class="text-center my-6">
        <div class="text-4xl mb-3">📖</div>
        <h1 class="text-2xl font-bold mb-2">{{ __('afiliator.pan_title') }}</h1>
        <p class="text-white/40 text-sm leading-relaxed">{{ __('afiliator.pan_intro') }}</p>
    </div>

    <div class="{{ $card }}"><h2 class="{{ $h2 }}">{{ __('afiliator.pan_langkah_title') }}</h2>
        <ol class="{{ $ol }}">@foreach(__('afiliator.pan_langkah') as $l)<li>{{ __($l, $p) }}</li>@endforeach</ol></div>

    <div class="{{ $card }}"><h2 class="{{ $h2 }}">{{ __('afiliator.pan_bayar_title') }}</h2>
        <ol class="{{ $ol }}">@foreach(__('afiliator.pan_bayar') as $l)<li>{{ __($l, $p) }}</li>@endforeach</ol></div>

    <div class="{{ $card }}"><h2 class="{{ $h2 }}">{{ __('afiliator.pan_tips_title') }}</h2>
        <ul class="{{ $ul }}">@foreach(__('afiliator.pan_tips') as $l)<li>{{ $l }}</li>@endforeach</ul></div>

    <div class="{{ $card }}"><h2 class="{{ $h2 }}">{{ __('afiliator.pan_masalah_title') }}</h2>
        <p class="text-sm text-white/60 leading-relaxed">{{ __('afiliator.pan_masalah') }}</p></div>
</div>
@endsection
