@extends('layouts.app')

@section('title', __('trial.title'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">

    {{-- Header singkat --}}
    <div class="text-center mb-8">
        <a href="{{ route('trial.index') }}" class="text-white/30 hover:text-white/50 text-xs transition inline-block mb-4">
            {{ __('trial.mulai_header.kembali') }}
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold leading-tight mb-2">{{ __('trial.mulai_header.title') }}</h1>
        <p class="text-white/50 text-sm leading-relaxed max-w-md mx-auto">{{ __('trial.mulai_header.desc') }}</p>
    </div>

    {{-- Form --}}
    <div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 sm:p-8 backdrop-blur-sm"
         x-data="trialForm()">

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6 text-red-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('trial.proses') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Nama --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.nama_label') }}</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="{{ __('trial.form.nama_placeholder') }}"
                    required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/30 transition"
                >
            </div>

            {{-- Jenis Kelamin --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.jenis_kelamin_label') }}</label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['Laki-laki' => __('trial.form.laki_laki'), 'Perempuan' => __('trial.form.perempuan')] as $val => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="jenis_kelamin" value="{{ $val }}"
                               {{ old('jenis_kelamin') === $val ? 'checked' : '' }} required class="sr-only peer">
                        <div class="py-3 rounded-xl border border-white/15 text-center text-sm font-medium text-white/50
                                    peer-checked:border-purple-500/60 peer-checked:bg-purple-500/15 peer-checked:text-purple-300
                                    hover:border-white/30 transition-all duration-150">
                            {{ $label }}
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Tanggal Lahir --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.dob_label') }}</label>
                <input
                    type="date"
                    name="dob"
                    value="{{ old('dob') }}"
                    required
                    max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 focus:ring-1 focus:ring-purple-500/30 transition [color-scheme:dark]"
                >
            </div>

            {{-- Jam Lahir --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.birth_hour_label') }}</label>
                <select
                    name="birth_hour"
                    required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark]"
                >
                    <option value="">{{ __('trial.form.birth_hour_placeholder') }}</option>
                    @for ($h = 0; $h <= 23; $h++)
                        <option value="{{ $h }}" {{ old('birth_hour') == $h ? 'selected' : '' }}>
                            {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 WIB
                        </option>
                    @endfor
                </select>
            </div>

            {{-- Divider Lokasi --}}
            <div class="flex items-center gap-3 pt-1">
                <div class="flex-1 h-px bg-white/5"></div>
                <span class="text-white/20 text-xs">{{ __('trial.form.divider_lokasi') }}</span>
                <div class="flex-1 h-px bg-white/5"></div>
            </div>

            {{-- Provinsi --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.provinsi_label') }}</label>
                <select x-model="selectedProvinsiId" @change="onProvinsiChange()"
                    required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark]">
                    <option value="">{{ __('trial.form.provinsi_placeholder') }}</option>
                    <template x-for="p in provinsiList" :key="p.id">
                        <option :value="p.id" x-text="p.nama"></option>
                    </template>
                </select>
                <input type="hidden" name="province" :value="province">
            </div>

            {{-- Kota/Kabupaten --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.kota_label') }}</label>
                <select x-model="selectedKotaId" @change="onKotaChange()"
                    :disabled="!kotaList.length"
                    required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark] disabled:opacity-40">
                    <option value="">{{ __('trial.form.kota_placeholder') }}</option>
                    <template x-for="k in kotaList" :key="k.id">
                        <option :value="k.id" x-text="labelKota(k)"></option>
                    </template>
                </select>
                <input type="hidden" name="kota" :value="kota">
            </div>

            {{-- Kecamatan --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.kecamatan_label') }}</label>
                <select x-model="selectedKecamatanId" @change="onKecamatanChange()"
                    :disabled="!kecamatanList.length"
                    required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark] disabled:opacity-40">
                    <option value="">{{ __('trial.form.kecamatan_placeholder') }}</option>
                    <template x-for="k in kecamatanList" :key="k.id">
                        <option :value="k.id" x-text="k.nama"></option>
                    </template>
                </select>
                <input type="hidden" name="kecamatan" :value="kecamatan">
            </div>

            {{-- Kelurahan --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.kelurahan_label') }}</label>
                <select x-model="selectedKelurahanId" @change="onKelurahanChange()"
                    :disabled="!kelurahanList.length"
                    required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark] disabled:opacity-40">
                    <option value="">{{ __('trial.form.kelurahan_placeholder') }}</option>
                    <template x-for="k in kelurahanList" :key="k.id">
                        <option :value="k.id" x-text="k.nama"></option>
                    </template>
                </select>
                <input type="hidden" name="kelurahan" :value="kelurahan">
            </div>

            {{-- Divider Konteks --}}
            <div class="flex items-center gap-3 pt-1">
                <div class="flex-1 h-px bg-white/5"></div>
                <span class="text-white/20 text-xs">{{ __('trial.form.divider_konteks') }}</span>
                <div class="flex-1 h-px bg-white/5"></div>
            </div>

            {{-- Agama (opsional) --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">
                    {{ __('trial.form.agama_label') }} <span class="normal-case text-white/30">({{ __('trial.form.opsional') }})</span>
                </label>
                <select name="agama" x-model="agamaPilihan"
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark]">
                    <option value="">{{ __('trial.form.agama_placeholder') }}</option>
                    @foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Kepercayaan terhadap Tuhan YME','Agnostik','Ateis','Others'] as $ag)
                    <option value="{{ $ag }}" {{ old('agama') === $ag ? 'selected' : '' }}>{{ $ag }}</option>
                    @endforeach
                </select>
                <div x-show="agamaPilihan === 'Others'" class="mt-3">
                    <input type="text" name="agama_lainnya" x-bind:required="agamaPilihan === 'Others'"
                        value="{{ old('agama_lainnya') }}"
                        placeholder="{{ __('trial.form.agama_lainnya_placeholder') }}"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:border-purple-500/60 transition">
                </div>
            </div>

            {{-- Status Pernikahan --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.status_label') }}</label>
                <select name="status_pernikahan" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark]">
                    <option value="">{{ __('trial.form.status_placeholder') }}</option>
                    @foreach(['Lajang','Menikah','Cerai Hidup','Cerai Mati'] as $st)
                    <option value="{{ $st }}" {{ old('status_pernikahan') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Urutan Anak --}}
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('trial.form.urutan_label') }}</label>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 flex-1 bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 focus-within:border-purple-500/60 transition">
                        <span class="text-white/30 text-sm whitespace-nowrap">{{ __('trial.form.anak_ke') }}</span>
                        <input type="number" name="anak_ke" min="1" max="20" required
                            value="{{ old('anak_ke') }}"
                            class="w-12 bg-transparent text-white text-center focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                    </div>
                    <span class="text-white/30 text-sm">{{ __('trial.form.dari') }}</span>
                    <div class="flex items-center gap-2 flex-1 bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 focus-within:border-purple-500/60 transition">
                        <input type="number" name="jumlah_saudara" min="1" max="20" required
                            value="{{ old('jumlah_saudara') }}"
                            class="w-12 bg-transparent text-white text-center focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                        <span class="text-white/30 text-sm whitespace-nowrap">{{ __('trial.form.bersaudara') }}</span>
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 shadow-lg shadow-purple-900/40 active:scale-[0.98]"
            >
                {{ __('trial.form.submit') }}
            </button>
        </form>

        <p class="text-center text-white/25 text-xs mt-4">
            {{ __('trial.form.no_account_needed') }}
        </p>
    </div>

    {{-- Disclaimer --}}
    <p class="mt-6 text-center text-xs text-white/25 leading-relaxed">
        {{ __('trial.disclaimer') }} <a href="https://www.primbon.com" target="_blank" rel="noopener" class="underline underline-offset-2 hover:text-white/40 transition">primbon.com</a>
    </p>
</div>

@push('scripts')
<script>
function trialForm() {
    return {
        // Lokasi state — teks ini yang benar-benar dikirim ke server (lewat hidden input),
        // selalu diturunkan dari ID terpilih, bukan sebaliknya.
        province: '{{ old('province') }}',
        kota:     '{{ old('kota') }}',
        kecamatan:'{{ old('kecamatan') }}',
        kelurahan:'{{ old('kelurahan') }}',
        agamaPilihan: '{{ old('agama') }}',

        // ID adalah sumber kebenaran untuk cascading. Nama kota/kabupaten bisa kembar
        // (mis. "Bekasi" dipakai oleh Kota Bekasi maupun Kabupaten Bekasi), jadi mencocokkan
        // pilihan lewat nama saja tidak aman — harus lewat ID.
        selectedProvinsiId:  '',
        selectedKotaId:      '',
        selectedKecamatanId: '',
        selectedKelurahanId: '',

        provinsiList:   [],
        kotaList:       [],
        kecamatanList:  [],
        kelurahanList:  [],

        labelKota(k) {
            return (k.tipe === 'Kota' ? 'Kota ' : 'Kab. ') + k.nama;
        },

        async init() {
            // Load provinsi list
            const res = await fetch('/api/wilayah/provinsi');
            this.provinsiList = await res.json();

            // Jika ada old() value (form validation error), restore cascade
            if (this.province) {
                const prov = this.provinsiList.find(p => p.nama === this.province);
                if (prov) {
                    this.selectedProvinsiId = prov.id;
                    await this.loadKota(prov.id);
                }
            }
            if (this.kota && this.kotaList.length) {
                const k = this.kotaList.find(k => this.labelKota(k) === this.kota);
                if (k) {
                    this.selectedKotaId = k.id;
                    await this.loadKecamatan(k.id);
                }
            }
            if (this.kecamatan && this.kecamatanList.length) {
                const kec = this.kecamatanList.find(k => k.nama === this.kecamatan);
                if (kec) {
                    this.selectedKecamatanId = kec.id;
                    await this.loadKelurahan(kec.id);
                }
            }
            if (this.kelurahan && this.kelurahanList.length) {
                const kel = this.kelurahanList.find(k => k.nama === this.kelurahan);
                if (kel) this.selectedKelurahanId = kel.id;
            }
        },

        onProvinsiChange() {
            const prov = this.provinsiList.find(p => p.id == this.selectedProvinsiId);
            this.province = prov ? prov.nama : '';
            this.selectedKotaId = ''; this.selectedKecamatanId = ''; this.selectedKelurahanId = '';
            this.kota = ''; this.kecamatan = ''; this.kelurahan = '';
            this.kotaList = []; this.kecamatanList = []; this.kelurahanList = [];
            if (this.selectedProvinsiId) this.loadKota(this.selectedProvinsiId);
        },

        onKotaChange() {
            const k = this.kotaList.find(k => k.id == this.selectedKotaId);
            this.kota = k ? this.labelKota(k) : '';
            this.selectedKecamatanId = ''; this.selectedKelurahanId = '';
            this.kecamatan = ''; this.kelurahan = '';
            this.kecamatanList = []; this.kelurahanList = [];
            if (this.selectedKotaId) this.loadKecamatan(this.selectedKotaId);
        },

        onKecamatanChange() {
            const kec = this.kecamatanList.find(k => k.id == this.selectedKecamatanId);
            this.kecamatan = kec ? kec.nama : '';
            this.selectedKelurahanId = '';
            this.kelurahan = '';
            this.kelurahanList = [];
            if (this.selectedKecamatanId) this.loadKelurahan(this.selectedKecamatanId);
        },

        onKelurahanChange() {
            const kel = this.kelurahanList.find(k => k.id == this.selectedKelurahanId);
            this.kelurahan = kel ? kel.nama : '';
        },

        async loadKota(provinsiId) {
            const res = await fetch(`/api/wilayah/kota?provinsi_id=${provinsiId}`);
            this.kotaList = await res.json();
        },

        async loadKecamatan(kotaId) {
            const res = await fetch(`/api/wilayah/kecamatan?kota_id=${kotaId}`);
            this.kecamatanList = await res.json();
        },

        async loadKelurahan(kecamatanId) {
            const res = await fetch(`/api/wilayah/kelurahan?kecamatan_id=${kecamatanId}`);
            this.kelurahanList = await res.json();
        },
    }
}
</script>
@endpush
@endsection
