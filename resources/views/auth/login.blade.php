@extends('layouts.app')

@section('title', __('auth.title'))

@section('content')
<div class="max-w-md mx-auto px-4 py-16">

    <div class="text-center mb-8">
        <img src="{{ asset('images/logo.png') }}" alt="Rumus Langit" class="h-40 w-auto mx-auto mb-4 drop-shadow-[0_0_32px_rgba(212,175,55,0.25)]">
        <h1 class="text-2xl font-bold mb-1">{{ __('auth.heading') }}</h1>
        <p class="text-white/40 text-sm">{{ __('auth.subtitle') }}</p>
    </div>

    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6">

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 mb-5 text-red-300 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-white/50 mb-1.5 uppercase tracking-wider">{{ __('auth.email_label') }}</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition"
                    placeholder="{{ __('auth.email_placeholder') }}"
                >
            </div>

            <div>
                <label class="block text-xs font-medium text-white/50 mb-1.5 uppercase tracking-wider">{{ __('auth.password_label') }}</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition"
                    placeholder="••••••••"
                >
            </div>

            <button
                type="submit"
                class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40 mt-2"
            >
                {{ __('auth.submit') }}
            </button>
        </form>
    </div>

    <p class="text-center mt-6">
        <a href="{{ route('trial.index') }}" class="text-white/30 hover:text-white/50 text-xs transition">
            {{ __('auth.coba_trial') }}
        </a>
    </p>

</div>
@endsection
