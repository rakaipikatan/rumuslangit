@extends('layouts.app')

@section('title', __('legal.' . $kunci . '.judul'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
    <h1 class="text-2xl font-bold mb-2">{{ __('legal.' . $kunci . '.judul') }}</h1>
    <p class="text-white/40 text-xs mb-8">{{ __('legal.diperbarui') }}</p>

    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 space-y-6">
        @foreach(__('legal.' . $kunci . '.bagian') as $b)
            <section>
                <h2 class="font-semibold text-base mb-2 text-white">{{ $b['h'] }}</h2>
                @foreach($b['p'] as $p)
                    <p class="text-sm text-white/60 leading-relaxed mb-2">{{ $p }}</p>
                @endforeach
                @if(!empty($b['li']))
                    <ul class="list-disc pl-5 space-y-1.5 text-sm text-white/60 leading-relaxed">
                        @foreach($b['li'] as $li)<li>{{ $li }}</li>@endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>

    <div class="mt-6 bg-purple-500/10 border border-purple-500/20 rounded-2xl p-5 text-sm text-white/70 leading-relaxed">
        <b>💬 {{ __('legal.kontak_judul') }}</b><br>{{ __('legal.kontak_isi') }}
    </div>

    <p class="text-center text-xs text-white/40 mt-6 space-x-3">
        <a href="{{ route('legal.privasi') }}" class="underline">{{ __('legal.privasi.judul') }}</a>
        <a href="{{ route('legal.syarat') }}" class="underline">{{ __('legal.syarat.judul') }}</a>
        <a href="{{ route('legal.refund') }}" class="underline">{{ __('legal.refund.judul') }}</a>
    </p>
</div>
@endsection
