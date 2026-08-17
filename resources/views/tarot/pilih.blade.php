@extends('layouts.app')

@section('title', __('tarot.title'))
@section('description', __('tarot.description'))

@push('head')
<style>
.card-scene { cursor: pointer; position: relative; transition: transform .15s; }
.card-scene:hover:not(.picked):not(.disabled) { transform: translateY(-4px); }

/* flip 3D */
.flip-scene { perspective: 700px; }
.flip-inner  {
    position: relative; width: 100%; height: 100%;
    transform-style: preserve-3d;
    transition: transform .7s cubic-bezier(.4,0,.2,1);
}
.flip-scene.flipped .flip-inner { transform: rotateY(180deg); }
.flip-face {
    position: absolute; inset: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 10px; overflow: hidden;
}
.flip-back {
    background: linear-gradient(145deg, #1e1b4b 0%, #312e81 50%, #1a1745 100%);
    border: 1.5px solid rgba(124,58,237,0.45);
    display: flex; align-items: center; justify-content: center; flex-direction: column;
}
.flip-back-inner {
    position: absolute; inset: 6px;
    border: 1px solid rgba(124,58,237,0.2);
    border-radius: 6px;
    pointer-events: none;
}
.flip-front {
    transform: rotateY(180deg);
    background: #0d0d2b;
    border: 1.5px solid rgba(124,58,237,0.3);
    display: flex; flex-direction: column;
}

/* selection ring on grid cards */
.card-scene.picked .flip-back {
    border-color: #f59e0b;
    box-shadow: 0 0 14px rgba(245,158,11,.55), 0 0 0 2px rgba(245,158,11,.25);
}
</style>
@endpush

@section('content')
{{-- Card data in script tag (avoids HTML attribute encoding issues) --}}
<script>
const TAROT_ALL = @json(array_values($kartu));
const TAROT_BACK = @json($back ?? null);
</script>

<div class="max-w-5xl mx-auto px-4 py-8" x-data="tarotPilih()">

    {{-- Header --}}
    <div class="text-center mb-6">
        <div class="text-3xl mb-2">🃏</div>
        <h1 class="text-2xl font-bold text-white">{{ __('tarot.heading') }}</h1>
        <p class="text-white/50 text-sm mt-1">{{ __('tarot.subtitle') }}</p>
    </div>

    {{-- ── FASE PILIH ─────────────────────────────────────────────────── --}}
    <div x-show="phase === 'pilih'">

        {{-- Status --}}
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-white/40">
                {{ __('tarot.terpilih') }} <span class="font-bold text-amber-400" x-text="picked.length"></span>/3
            </p>
            <button
                x-show="picked.length === 3"
                x-transition
                @click="doReveal()"
                class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-black font-bold rounded-xl text-sm transition">
                {{ __('tarot.buka_kartu') }}
            </button>
        </div>

        {{-- Grid 78 kartu tertutup --}}
        <div class="grid gap-1.5" style="grid-template-columns: repeat(auto-fill, minmax(54px, 1fr));">
            <template x-for="card in shuffled" :key="card.id">
                <div
                    class="card-scene"
                    style="height:80px;"
                    :class="{
                        'picked'   : picked.includes(card.id),
                        'disabled' : picked.length === 3 && !picked.includes(card.id),
                        'opacity-25 pointer-events-none': picked.length === 3 && !picked.includes(card.id)
                    }"
                    @click="togglePick(card.id)">

                    {{-- Card back --}}
                    <div class="flip-back rounded-lg w-full h-full flex items-center justify-center">
                        <div class="flip-back-inner"></div>
                        <span style="font-size:18px;opacity:.35;">✦</span>
                    </div>

                    {{-- Nomor urut pilihan --}}
                    <div x-show="picked.includes(card.id)"
                         class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-amber-400 text-black text-xs font-black flex items-center justify-center z-10"
                         x-text="picked.indexOf(card.id) + 1"></div>
                </div>
            </template>
        </div>

        <p class="text-center text-white/30 text-xs mt-4">
            <span x-show="picked.length < 3">{{ __('tarot.klik_pilih') }}</span>
            <span x-show="picked.length === 3" class="text-amber-400/70">{!! __('tarot.tiga_terpilih', ['tombol' => '<strong>'.__('tarot.tombol_buka').'</strong>']) !!}</span>
        </p>
    </div>

    {{-- ── FASE REVEAL ─────────────────────────────────────────────────── --}}
    <div x-show="phase === 'reveal'" style="display:none;"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0">

        <h2 class="text-center text-sm font-semibold text-white/40 uppercase tracking-widest mb-6">{{ __('tarot.kartu_pilihanmu') }}</h2>

        {{-- 3 kartu reveal --}}
        <div class="flex flex-col sm:flex-row gap-6 justify-center items-start mb-8">
            <template x-for="(card, idx) in chosen" :key="card.id">
                <div class="flex flex-col items-center gap-3" style="flex:1;max-width:190px;">

                    {{-- Kartu flip --}}
                    <div class="flip-scene w-full" style="height:240px;"
                         :class="{ flipped: card.flipped }"
                         @click="card.flipped = !card.flipped">
                        <div class="flip-inner" style="height:240px;">
                            {{-- Belakang --}}
                            <div class="flip-face flip-back">
                                <div class="flip-back-inner"></div>
                                <span style="font-size:28px;opacity:.3;">✦</span>
                                <span class="text-purple-300/40 text-xs mt-1">{{ __('tarot.klik_buka') }}</span>
                            </div>
                            {{-- Depan --}}
                            <div class="flip-face flip-front">
                                <img :src="card.image" :alt="card.nama"
                                     style="height:155px;object-fit:cover;width:100%;flex-shrink:0;border-radius:8px 8px 0 0;">
                                <div class="px-2 py-1.5 flex-1 overflow-hidden">
                                    <div class="flex items-center gap-1">
                                        <span x-text="card.simbol" class="text-sm"></span>
                                        <span class="text-xs font-bold text-white leading-tight" x-text="card.nama"></span>
                                    </div>
                                    <div class="text-xs text-purple-300/60 mb-1" x-text="card.subtitle"></div>
                                    <div class="text-xs text-white/55 leading-snug"
                                         x-text="card.terbalik ? card.makna_terbalik : card.makna_tegak"
                                         style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Makna (setelah flip) --}}
                    <div x-show="card.flipped" x-transition
                         class="w-full rounded-xl bg-white/5 border border-white/10 p-3 text-xs leading-relaxed">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-white" x-text="card.simbol + ' ' + card.nama"></span>
                            <span class="text-white/25 font-mono" x-text="card.roman"></span>
                        </div>
                        <p class="text-white/40 italic mb-1.5" x-text="card.subtitle"></p>
                        <div class="flex items-start gap-1">
                            <span x-show="!card.terbalik" class="text-green-400 shrink-0 font-bold">↑</span>
                            <span x-show="card.terbalik" class="text-red-400 shrink-0 font-bold">↓</span>
                            <span class="text-white/70" x-text="card.terbalik ? card.makna_terbalik : card.makna_tegak"></span>
                        </div>
                        <p class="mt-1.5 text-white/25 text-xs" x-text="card.terbalik ? '{{ __('tarot.kartu_terbalik') }}' : '{{ __('tarot.kartu_tegak') }}'"></p>
                    </div>
                </div>
            </template>
        </div>

        {{-- Tombol aksi --}}
        <div class="flex flex-wrap justify-center gap-3">
            <button x-show="chosen.some(c => !c.flipped)" @click="flipAll()"
                    class="px-5 py-2.5 bg-violet-600 hover:bg-violet-500 text-white font-semibold rounded-xl text-sm transition">
                {{ __('tarot.buka_semua') }}
            </button>

            @if($back)
            <button @click="gunakanKartu()"
                    class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-black font-bold rounded-xl text-sm transition">
                {{ __('tarot.gunakan_kartu') }}
            </button>
            @endif
        </div>

        <p class="text-center text-white/20 text-xs mt-6 max-w-sm mx-auto">
            {{ __('tarot.footer_note') }}
        </p>
    </div>

</div>
@endsection

@push('scripts')
<script>
function tarotPilih() {
    return {
        allCards : [],
        shuffled : [],
        picked   : [],
        chosen   : [],
        phase    : 'pilih',

        init() {
            this.allCards = TAROT_ALL.map((c, i) => ({ ...c, id: i }));
            this.doShuffle();
        },

        doShuffle() {
            const a = [...this.allCards];
            for (let i = a.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [a[i], a[j]] = [a[j], a[i]];
            }
            this.shuffled = a;
        },

        togglePick(id) {
            if (this.picked.includes(id)) {
                this.picked = this.picked.filter(x => x !== id);
            } else if (this.picked.length < 3) {
                this.picked.push(id);
            }
        },

        doReveal() {
            if (this.picked.length !== 3) return;
            this.chosen = this.picked.map(id => ({
                ...this.allCards.find(c => c.id === id),
                flipped  : false,
                terbalik : Math.random() < 0.3,
            }));
            this.phase = 'reveal';
            // Auto-flip bertahap
            this.$nextTick(() => {
                this.chosen.forEach((_, i) => {
                    setTimeout(() => { this.chosen[i].flipped = true; }, 600 + i * 700);
                });
            });
        },

        flipAll() {
            this.chosen.forEach((c, i) => {
                setTimeout(() => { this.chosen[i].flipped = true; }, i * 300);
            });
        },

        gunakanKartu() {
            if (!TAROT_BACK || this.chosen.length !== 3) return;
            // Simpan ke localStorage, ambil di form
            const key = 'tarot_for_' + TAROT_BACK;
            localStorage.setItem(key, JSON.stringify(this.chosen));
            window.location.href = '/laporan/' + TAROT_BACK;
        },

        reset() {
            this.picked  = [];
            this.chosen  = [];
            this.phase   = 'pilih';
            this.doShuffle();
        },
    };
}
</script>
@endpush
