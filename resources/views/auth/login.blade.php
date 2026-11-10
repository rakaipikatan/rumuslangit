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

        {{-- Masuk dengan kode email (utama: pengguna tidak punya password) --}}
        <div x-data="{ step: 1, email: '', kode: '', pesan: '', loading: false,
            async post(url, body) {
                const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(body) });
                let j = {}; try { j = await r.json(); } catch (_) {}
                return { ok: r.ok, j };
            },
            async kirim() {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) { this.pesan = '{{ __('auth.kode_error') }}'; return; }
                this.loading = true; this.pesan = '';
                const { ok, j } = await this.post('{{ route('auth.kode.kirim') }}', { email: this.email });
                this.loading = false;
                if (ok) { this.step = 2; this.pesan = j.message || ''; } else { this.pesan = (j.message) || '{{ __('auth.kode_error') }}'; }
            },
            async masuk() {
                this.loading = true; this.pesan = '';
                const { ok, j } = await this.post('{{ route('auth.kode.verifikasi') }}', { kode_otp: this.kode });
                this.loading = false;
                if (ok && j.redirect) { window.location.href = j.redirect; } else { this.pesan = j.message || '{{ __('auth.kode_error') }}'; }
            } }" class="space-y-4 mb-5">
            <div>
                <h2 class="text-sm font-semibold text-white">{{ __('auth.kode_title') }}</h2>
                <p class="text-xs text-white/40 mt-1">{{ __('auth.kode_desc') }}</p>
            </div>

            <div x-show="step === 1" class="space-y-3">
                <input type="email" x-model="email" @keyup.enter.prevent="kirim()" placeholder="{{ __('auth.email_placeholder') }}"
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition">
                <button type="button" @click.prevent="kirim()" :disabled="loading"
                    class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40 disabled:opacity-60">
                    <span x-show="!loading">{{ __('auth.kode_kirim') }}</span><span x-show="loading" style="display:none">{{ __('auth.kode_mengirim') }}</span>
                </button>
            </div>

            <div x-show="step === 2" style="display:none" class="space-y-3">
                <input type="text" x-model="kode" maxlength="6" inputmode="numeric" autocomplete="one-time-code" @keyup.enter.prevent="masuk()" placeholder="{{ __('auth.kode_placeholder') }}"
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-center text-xl tracking-[0.4em] text-white placeholder-white/25 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/20 transition">
                <button type="button" @click.prevent="masuk()" :disabled="loading || kode.length !== 6"
                    class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40 disabled:opacity-60">
                    {{ __('auth.kode_masuk') }}
                </button>
                <button type="button" @click.prevent="step = 1; kode = ''; pesan = ''" class="w-full text-white/30 text-xs hover:text-white/50 transition">{{ __('auth.kode_ubah_email') }}</button>
            </div>

            <p x-show="pesan" x-text="pesan" class="text-xs text-amber-300/80"></p>
        </div>

        <details class="border-t border-white/5 pt-4" @if($errors->any()) open @endif>
            <summary class="text-xs text-white/40 hover:text-white/60 cursor-pointer select-none">{{ __('auth.kode_password_toggle') }}</summary>
        <form method="POST" action="{{ route('auth.login') }}" class="space-y-4 mt-4">
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
        </details>
    </div>

    <p class="text-center mt-6">
        <a href="{{ route('trial.index') }}" class="text-white/30 hover:text-white/50 text-xs transition">
            {{ __('auth.coba_trial') }}
        </a>
    </p>

</div>
@endsection
