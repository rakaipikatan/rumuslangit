{{-- Widget chat bantuan otomatis (semua halaman) --}}
<div x-data="bantuanChat()" x-cloak class="no-print fixed bottom-4 right-4 z-40 text-sm">
    <button type="button" x-show="!buka" @click="buka = true; $nextTick(() => $refs.isi?.focus())"
            class="flex items-center gap-2 bg-purple-600 hover:bg-purple-500 shadow-lg shadow-purple-900/40 rounded-full pl-4 pr-5 py-3 font-medium transition">
        <span>💬</span><span>{{ __('bantuan.tombol') }}</span>
    </button>

    <div x-show="buka" x-transition class="w-[calc(100vw-2rem)] sm:w-80 h-[28rem] max-h-[75vh] flex flex-col bg-cosmic-800 border border-white/10 rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 bg-cosmic-700/60">
            <span class="font-semibold">💬 {{ __('bantuan.judul') }}</span>
            <button type="button" @click="buka = false" class="text-white/50 hover:text-white text-xs" aria-label="{{ __('bantuan.tutup') }}">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-3 space-y-2.5" x-ref="daftar">
            <div class="bg-white/5 rounded-xl rounded-tl-sm px-3 py-2 text-white/80 leading-relaxed">{{ __('bantuan.sapaan') }}</div>
            <template x-for="(m, i) in pesan" :key="i">
                <div :class="m.r === 'u' ? 'flex justify-end' : ''">
                    <div class="max-w-[88%] px-3 py-2 rounded-xl leading-relaxed whitespace-pre-line"
                         :class="m.r === 'u' ? 'bg-purple-600 rounded-tr-sm' : 'bg-white/5 text-white/80 rounded-tl-sm'" x-text="m.t"></div>
                </div>
            </template>
            <div x-show="memuat" class="text-xs text-white/40 px-1">{{ __('bantuan.berpikir') }}</div>
        </div>

        <form @submit.prevent="kirim()" class="flex gap-2 p-3 border-t border-white/10">
            <input x-ref="isi" x-model="teks" maxlength="500" placeholder="{{ __('bantuan.placeholder') }}" :disabled="memuat"
                   class="flex-1 min-w-0 bg-cosmic-700/60 border border-white/10 rounded-xl px-3 py-2 text-white placeholder-white/30 focus:outline-none focus:border-purple-500/60">
            <button :disabled="memuat || teks.trim().length < 2" class="bg-purple-600 hover:bg-purple-500 disabled:opacity-40 rounded-xl px-3 font-medium transition">{{ __('bantuan.kirim') }}</button>
        </form>
    </div>
</div>

<script>
function bantuanChat() {
    return {
        buka: false, teks: '', memuat: false, pesan: [],
        async kirim() {
            const t = this.teks.trim();
            if (t.length < 2 || this.memuat) return;
            const riwayat = this.pesan.slice(-6).map(m => ({ r: m.r, t: m.t }));
            this.pesan.push({ r: 'u', t });
            this.teks = ''; this.memuat = true; this.gulir();
            try {
                const r = await fetch('{{ route('bantuan.chat') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ pesan: t, riwayat }),
                });
                if (r.status === 429) {
                    const j = await r.json().catch(() => ({}));
                    this.pesan.push({ r: 'b', t: j.jawaban || @json(__('bantuan.terlalu_cepat')) });
                } else if (!r.ok) {
                    this.pesan.push({ r: 'b', t: @json(__('bantuan.gagal')) });
                } else {
                    this.pesan.push({ r: 'b', t: (await r.json()).jawaban });
                }
            } catch (e) {
                this.pesan.push({ r: 'b', t: @json(__('bantuan.gagal')) });
            }
            this.memuat = false; this.gulir();
        },
        gulir() { this.$nextTick(() => { const d = this.$refs.daftar; if (d) d.scrollTop = d.scrollHeight; }); },
    };
}
</script>
