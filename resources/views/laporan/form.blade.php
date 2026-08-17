@extends('layouts.app')

@section('title', $fitur['nama'])

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-white/30 mb-8">
        <a href="{{ route('trial.katalog') }}" class="hover:text-white/50 transition">{{ __('laporan.form.kembali') }}</a>
        <span>/</span>
        <span class="text-white/50">{{ $fitur['hub'] }}</span>
        <span>/</span>
        <span>{{ $fitur['nama'] }}</span>
    </div>

    {{-- Header --}}
    @if(session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 mb-6 flex items-center gap-3 text-emerald-300 text-sm">
        <span class="text-lg">✓</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <div class="mb-8">
        <div class="inline-flex items-center gap-2 bg-cosmic-600/40 border border-purple-500/20 rounded-full px-3 py-1 text-xs text-purple-300 mb-4">
            {{ $fitur['hub'] }}
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold mb-2">
            <span class="mr-2">{{ $fitur['icon'] }}</span>{{ $fitur['nama'] }}
        </h1>
        <p class="text-white/40 text-sm leading-relaxed">{{ $fitur['deskripsi'] }}</p>
    </div>

    {{-- Identitas kalkulasi mini --}}
    @php $kalkulasi = session('kalkulasi'); @endphp
    @if ($kalkulasi)
    <div class="flex items-center gap-3 bg-cosmic-800/40 border border-white/5 rounded-xl px-4 py-3 mb-8 text-xs text-white/40">
        <span class="text-purple-300 font-medium">{{ $user->name }}</span>
        <span>·</span>
        <span>{{ $kalkulasi['weton']['weton'] }}</span>
        <span>·</span>
        <span>{{ $kalkulasi['shio']['nama'] }}</span>
        <span>·</span>
        <span>{{ $kalkulasi['zodiak'] }}</span>
    </div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 rounded-2xl px-4 py-4 mb-6">
        <p class="text-red-300 text-sm font-semibold mb-2">{{ __('laporan.form.error_title') }}</p>
        <ul class="text-red-300/80 text-xs space-y-1 list-disc list-inside">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Form kuesioner --}}
    <form action="{{ route('laporan.proses', $featureId) }}" method="POST"
          x-data="kuesionerForm()" @submit.prevent="submitForm">
        @csrf

        {{-- ═══ DATA PASANGAN (hanya feature 5: Jodoh & Asmara) ═══ --}}
        @if($featureId === 5)
        <div class="bg-cosmic-800/60 border border-pink-500/20 rounded-2xl p-6 mb-6">
            <div class="flex items-center gap-3 mb-5">
                <span class="text-2xl">💑</span>
                <div>
                    <h3 class="text-sm font-bold text-white">{{ __('laporan.form.pasangan_title') }}</h3>
                    <p class="text-xs text-white/40 mt-0.5">{{ __('laporan.form.pasangan_sub') }}</p>
                </div>
            </div>

            {{-- Nama pasangan --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.nama_pasangan_label') }}</label>
                    <input type="text" name="p_nama" required placeholder="{{ __('laporan.form.nama_pasangan_placeholder') }}"
                           value="{{ old('p_nama') }}"
                           class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:border-pink-500/60 transition">
                </div>

                {{-- Jenis Kelamin Pasangan (auto dari gender user, bisa diubah) --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.jenis_kelamin_pasangan_label') }}</label>
                    <input type="hidden" name="p_jenis_kelamin" :value="pasanganGender">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['Laki-laki' => __('laporan.form.laki_laki'), 'Perempuan' => __('laporan.form.perempuan')] as $val => $label)
                        <button type="button" @click="pasanganGender = '{{ $val }}'"
                            class="py-3 rounded-xl border text-center text-sm font-medium transition-all cursor-pointer"
                            :class="pasanganGender === '{{ $val }}'
                                ? 'border-pink-500/60 bg-pink-500/15 text-pink-300'
                                : 'border-white/10 text-white/30 hover:border-white/30'">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                    <p class="text-xs text-white/30 mt-1.5">{{ __('laporan.form.auto_gender_note') }}</p>
                </div>

                {{-- Tanggal & Jam Lahir --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.tanggal_lahir_label') }}</label>
                        <input type="date" name="p_dob" required
                               max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                               value="{{ old('p_dob') }}"
                               class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.jam_lahir_label') }} <span class="normal-case text-white/30">{{ __('laporan.form.boleh_kosong') }}</span></label>
                        <select name="p_birth_hour"
                                class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark]">
                            <option value="">{{ __('laporan.form.tidak_tahu') }}</option>
                            @for ($h = 0; $h <= 23; $h++)
                            <option value="{{ $h }}" {{ old('p_birth_hour') == $h && old('p_birth_hour') !== null ? 'selected' : '' }}>{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 WIB</option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- Divider Lokasi --}}
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-px bg-white/5"></div>
                    <span class="text-white/20 text-xs">{{ __('laporan.form.divider_lokasi_pasangan') }}</span>
                    <div class="flex-1 h-px bg-white/5"></div>
                </div>

                {{-- Provinsi Pasangan --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.provinsi_label') }}</label>
                    <select x-model="pSelectedProvId" @change="onPProvinsiChange()" required
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark]">
                        <option value="">{{ __('laporan.form.provinsi_placeholder') }}</option>
                        <template x-for="p in provinsiList" :key="p.id">
                            <option :value="p.id" x-text="p.nama"></option>
                        </template>
                    </select>
                    <input type="hidden" name="p_province" :value="pProvince">
                </div>

                {{-- Kota Pasangan --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.kota_label') }}</label>
                    <select x-model="pSelectedKotaId" @change="onPKotaChange()" required
                        :disabled="!pKotaList.length"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark] disabled:opacity-40">
                        <option value="">{{ __('laporan.form.kota_placeholder') }}</option>
                        <template x-for="k in pKotaList" :key="k.id">
                            <option :value="k.id" x-text="labelKota(k)"></option>
                        </template>
                    </select>
                    <input type="hidden" name="p_kota" :value="pKota">
                </div>

                {{-- Kecamatan Pasangan --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.kecamatan_label') }}</label>
                    <select x-model="pSelectedKecId" @change="onPKecamatanChange()" required
                        :disabled="!pKecamatanList.length"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark] disabled:opacity-40">
                        <option value="">{{ __('laporan.form.kecamatan_placeholder') }}</option>
                        <template x-for="k in pKecamatanList" :key="k.id">
                            <option :value="k.id" x-text="k.nama"></option>
                        </template>
                    </select>
                    <input type="hidden" name="p_kecamatan" :value="pKecamatan">
                </div>

                {{-- Kelurahan Pasangan --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.kelurahan_label') }}</label>
                    <select x-model="pSelectedKelId" @change="onPKelurahanChange()" required
                        :disabled="!pKelurahanList.length"
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark] disabled:opacity-40">
                        <option value="">{{ __('laporan.form.kelurahan_placeholder') }}</option>
                        <template x-for="k in pKelurahanList" :key="k.id">
                            <option :value="k.id" x-text="k.nama"></option>
                        </template>
                    </select>
                    <input type="hidden" name="p_kelurahan" :value="pKelurahan">
                </div>

                {{-- Divider Konteks --}}
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-px bg-white/5"></div>
                    <span class="text-white/20 text-xs">{{ __('laporan.form.divider_konteks_pasangan') }}</span>
                    <div class="flex-1 h-px bg-white/5"></div>
                </div>

                {{-- Agama --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.agama_label') }}</label>
                    <select name="p_agama" required
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark]">
                        <option value="">{{ __('laporan.form.agama_placeholder') }}</option>
                        @foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Lainnya'] as $ag)
                        <option value="{{ $ag }}" {{ old('p_agama') === $ag ? 'selected' : '' }}>{{ $ag }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.status_label') }}</label>
                    <select name="p_status_pernikahan" required
                        class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-pink-500/60 transition [color-scheme:dark]">
                        <option value="">{{ __('laporan.form.status_placeholder') }}</option>
                        @foreach(['Lajang','Menikah','Cerai Hidup','Cerai Mati'] as $st)
                        <option value="{{ $st }}" {{ old('p_status_pernikahan') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Urutan Lahir --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.urutan_label') }}</label>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 flex-1 bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 focus-within:border-pink-500/60 transition">
                            <span class="text-white/30 text-sm whitespace-nowrap">{{ __('laporan.form.anak_ke') }}</span>
                            <input type="number" name="p_anak_ke" min="1" max="20" required
                                value="{{ old('p_anak_ke') }}"
                                class="w-12 bg-transparent text-white text-center focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                        <span class="text-white/30 text-sm">{{ __('laporan.form.dari') }}</span>
                        <div class="flex items-center gap-2 flex-1 bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 focus-within:border-pink-500/60 transition">
                            <input type="number" name="p_jumlah_saudara" min="1" max="20" required
                                value="{{ old('p_jumlah_saudara') }}"
                                class="w-12 bg-transparent text-white text-center focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                            <span class="text-white/30 text-sm whitespace-nowrap">{{ __('laporan.form.bersaudara') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Pekerjaan --}}
                <div>
                    <label class="block text-xs font-medium text-white/60 mb-1.5 uppercase tracking-wider">{{ __('laporan.form.pekerjaan_label') }}</label>
                    <input type="text" name="p_pekerjaan" required placeholder="{{ __('laporan.form.pekerjaan_placeholder') }}"
                           value="{{ old('p_pekerjaan') }}"
                           class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:border-pink-500/60 transition">
                </div>
            </div>
        </div>
        {{-- /DATA PASANGAN --}}

        {{-- Divider ke pertanyaan --}}
        <div class="flex items-center gap-3 mb-6">
            <div class="flex-1 h-px bg-white/5"></div>
            <span class="text-white/20 text-xs">{{ __('laporan.form.divider_pertanyaan_relasi') }}</span>
            <div class="flex-1 h-px bg-white/5"></div>
        </div>
        @endif

        @if (!empty($fitur['tanya_golongan_darah']))
        <div class="bg-cosmic-800/60 border border-cyan-500/20 rounded-xl p-5 mb-4">
            <p class="text-sm font-medium text-white/80 mb-3">
                {{ __('laporan.form.golongan_darah_label') }}
            </p>
            <div class="grid grid-cols-4 gap-2">
                @foreach(['A','B','AB','O'] as $gd)
                <label class="cursor-pointer">
                    <input type="radio" name="golongan_darah" value="{{ $gd }}" required class="sr-only peer">
                    <div class="py-2.5 rounded-lg border border-white/15 text-center text-sm font-semibold text-white/50
                                peer-checked:border-cyan-500/60 peer-checked:bg-cyan-500/20 peer-checked:text-cyan-300
                                hover:border-white/30 transition-all duration-150">
                        {{ $gd }}
                    </div>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="space-y-4 mb-8">
            @foreach ($fitur['pertanyaan'] as $i => $pertanyaan)
            @php $nomor = $i + 1; @endphp
            <div class="bg-cosmic-800/60 border border-white/10 rounded-xl p-5 transition-all duration-200"
                 :class="jawaban.q{{ $nomor }} !== null ? 'border-purple-500/30' : ''">

                <p class="text-sm text-white/80 leading-relaxed mb-4">
                    <span class="text-white/30 mr-2">{{ $nomor }}.</span>{{ $pertanyaan }}
                </p>

                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="jawaban.q{{ $nomor }} = true"
                        :class="jawaban.q{{ $nomor }} === true
                            ? 'bg-emerald-500/20 border-emerald-500/60 text-emerald-300'
                            : 'bg-transparent border-white/15 text-white/40 hover:border-white/30'"
                        class="flex-1 py-2.5 rounded-lg border text-sm font-medium transition-all duration-150"
                    >
                        {{ __('laporan.form.ya') }}
                    </button>
                    <button
                        type="button"
                        @click="jawaban.q{{ $nomor }} = false"
                        :class="jawaban.q{{ $nomor }} === false
                            ? 'bg-red-500/20 border-red-500/60 text-red-300'
                            : 'bg-transparent border-white/15 text-white/40 hover:border-white/30'"
                        class="flex-1 py-2.5 rounded-lg border text-sm font-medium transition-all duration-150"
                    >
                        {{ __('laporan.form.tidak') }}
                    </button>
                </div>

                {{-- Hidden input untuk submit --}}
                <input type="hidden" name="q{{ $nomor }}"
                       :value="jawaban.q{{ $nomor }} !== null ? (jawaban.q{{ $nomor }} ? '1' : '0') : ''">
            </div>
            @endforeach
        </div>

        {{-- Progress --}}
        <div class="mb-6">
            <div class="flex justify-between text-xs text-white/30 mb-1.5">
                <span>{{ __('laporan.form.progress') }}</span>
                <span x-text="terjawab + ' / {{ count($fitur['pertanyaan']) }} {{ __('laporan.form.progress_suffix') }}'"></span>
            </div>
            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                <div
                    class="h-full bg-gradient-to-r from-purple-500 to-cyan-500 rounded-full transition-all duration-300"
                    :style="'width: ' + (terjawab / {{ count($fitur['pertanyaan']) }} * 100) + '%'"
                ></div>
            </div>
        </div>

        {{-- ═══ BONUS TAROT ═══ --}}
        @php
            $posisiTarot = \App\Data\TarotData::posisiKartu($featureId);
            $semuaKartu  = \App\Data\TarotData::semua();
        @endphp
        <div class="mb-6">
            <div class="border border-dashed border-amber-500/30 rounded-2xl overflow-hidden">
                {{-- Header toggle --}}
                <button type="button" @click="toggleTarot()"
                    class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-amber-500/5 transition">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🃏</span>
                        <div>
                            <div class="text-sm font-semibold text-amber-300">{{ __('laporan.form.tarot_title') }}</div>
                            <div class="text-xs text-white/40 mt-0.5">{{ __('laporan.form.tarot_sub') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span x-show="!tarotAktif" class="text-xs text-amber-400/70 bg-amber-500/10 border border-amber-500/20 rounded-full px-2.5 py-1">{{ __('laporan.form.tarot_aktifkan') }}</span>
                        <span x-show="tarotAktif && !kartusudahDiambil" class="text-xs text-amber-300 bg-amber-500/15 border border-amber-500/30 rounded-full px-2.5 py-1">{{ __('laporan.form.tarot_aktif') }}</span>
                        <span x-show="tarotAktif && kartusudahDiambil" class="text-xs text-emerald-300 bg-emerald-500/15 border border-emerald-500/30 rounded-full px-2.5 py-1">{{ __('laporan.form.tarot_dipilih') }}</span>
                        <svg class="w-4 h-4 text-white/30 transition-transform duration-200" :class="tarotAktif ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>

                {{-- Konten tarot --}}
                <div x-show="tarotAktif" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="border-t border-amber-500/20 px-5 pb-5 pt-4 bg-amber-500/5">

                    <p class="text-xs text-white/50 mb-4 leading-relaxed">
                        {{ __('laporan.form.tarot_desc') }}
                    </p>

                    {{-- Area kartu: placeholder saat belum diambil --}}
                    <div x-show="!kartusudahDiambil" class="grid grid-cols-3 gap-3 mb-4">
                        @foreach ($posisiTarot as $pi => $posNama)
                        <div class="relative rounded-2xl overflow-hidden" style="aspect-ratio:2/3;">
                            {{-- Card back design --}}
                            <div class="absolute inset-0 flex flex-col items-center justify-between p-3"
                                 style="background:linear-gradient(160deg,#1e1b4b,#0f0c29);border:1px solid rgba(167,139,250,0.2);">
                                {{-- Pattern --}}
                                <div class="absolute inset-0 opacity-10"
                                     style="background-image:repeating-linear-gradient(45deg,rgba(167,139,250,0.3) 0px,rgba(167,139,250,0.3) 1px,transparent 1px,transparent 8px);"></div>
                                <div class="absolute inset-2 border border-purple-500/20 rounded-xl"></div>
                                <div class="absolute inset-4 border border-purple-500/10 rounded-lg"></div>
                                {{-- Center ornament --}}
                                <div class="relative flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl opacity-40 mb-1">🃏</div>
                                    <div class="w-8 h-px bg-amber-500/30"></div>
                                </div>
                                {{-- Position label --}}
                                <p class="relative text-center text-amber-300/60 leading-tight z-10"
                                   style="font-size:9px;">{{ $posNama }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Area kartu hasil (setelah diambil) --}}
                    <div x-show="kartusudahDiambil" class="grid grid-cols-3 gap-3 mb-4">
                        <template x-for="(kartu, idx) in karteTerpilih" :key="idx">
                            <div class="relative rounded-2xl overflow-hidden cursor-default"
                                 style="aspect-ratio:2/3;"
                                 x-data="{ revealed: false }"
                                 x-init="setTimeout(() => revealed = true, idx * 300 + 100)">

                                {{-- Card face (hidden until revealed) --}}
                                <div class="absolute inset-0 flex flex-col"
                                     :style="'background:linear-gradient(160deg,' + (kartu.terbalik ? '#3b1a1a,#1a0a0a' : kartu.warna.split(\',\')[0]+\'20,\'+kartu.warna.split(\',\')[1]+\'10\') + ');border:1px solid ' + (kartu.terbalik ? 'rgba(239,68,68,0.35)' : 'rgba(251,191,36,0.35)')"
                                     x-show="revealed"
                                     x-transition:enter="transition duration-500"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100">

                                    {{-- Glow border --}}
                                    <div class="absolute inset-0 rounded-2xl pointer-events-none"
                                         :style="'box-shadow:inset 0 0 20px ' + (kartu.terbalik ? 'rgba(239,68,68,0.1)' : 'rgba(251,191,36,0.08)')"></div>

                                    {{-- Content --}}
                                    <div class="relative flex flex-col h-full" :class="kartu.terbalik ? 'rotate-180' : ''">
                                        {{-- Card image --}}
                                        <div class="flex-1 overflow-hidden rounded-t-xl">
                                            <img :src="kartu.image" :alt="kartu.nama"
                                                 class="w-full h-full object-cover"
                                                 style="border-radius:6px 6px 0 0;">
                                        </div>
                                        {{-- Name bar --}}
                                        <div class="text-center py-1.5 px-1"
                                             :style="'background:linear-gradient(to bottom,' + kartu.warna.split(\',\')[0]+\'30,\'+kartu.warna.split(\',\')[1]+\'20)'">
                                            <p class="font-bold leading-tight text-white" style="font-size:8.5px;" x-text="kartu.nama"></p>
                                            <p class="opacity-50 mt-0.5 text-white" style="font-size:7px;" x-text="kartu.subtitle"></p>
                                        </div>
                                    </div>

                                    {{-- Orientation badge --}}
                                    <div class="absolute bottom-1.5 right-1.5 z-10">
                                        <span class="text-xs rounded-full px-1.5 py-0.5 font-medium"
                                              :class="kartu.terbalik
                                                  ? 'bg-red-500/20 text-red-300 border border-red-500/30'
                                                  : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'"
                                              :style="'font-size:7px;'"
                                              x-text="kartu.terbalik ? '{{ __('laporan.form.tarot_terbalik') }}' : '{{ __('laporan.form.tarot_tegak') }}'"></span>
                                    </div>
                                </div>

                                {{-- Card back (shown before reveal) --}}
                                <div class="absolute inset-0" x-show="!revealed">
                                    <div class="absolute inset-0 flex items-center justify-center"
                                         style="background:linear-gradient(160deg,#1e1b4b,#0f0c29);border:1px solid rgba(167,139,250,0.2);">
                                        <div class="text-2xl animate-pulse opacity-40">🃏</div>
                                    </div>
                                </div>

                                {{-- Hidden inputs --}}
                                <input type="hidden" :name="'tarot[' + idx + '][id]'"       :value="kartu.id">
                                <input type="hidden" :name="'tarot[' + idx + '][nama]'"     :value="kartu.nama + ' — ' + kartu.subtitle">
                                <input type="hidden" :name="'tarot[' + idx + '][posisi]'"   :value="{{ json_encode($posisiTarot) }}[idx]">
                                <input type="hidden" :name="'tarot[' + idx + '][orientasi]'" :value="kartu.terbalik ? 'terbalik' : 'tegak'">
                                <input type="hidden" :name="'tarot[' + idx + '][makna]'"    :value="kartu.terbalik ? kartu.makna_terbalik : kartu.makna_tegak">
                            </div>
                        </template>
                    </div>

                    {{-- Makna kartu (expanded) --}}
                    <div x-show="kartusudahDiambil" class="space-y-2 mb-4">
                        <template x-for="(kartu, idx) in karteTerpilih" :key="'makna'+idx">
                            <div class="flex items-start gap-3 text-xs rounded-xl px-3 py-2.5"
                                 :class="kartu.terbalik ? 'bg-red-500/8 border border-red-500/15' : 'bg-amber-500/8 border border-amber-500/15'">
                                <span class="text-base flex-shrink-0 mt-0.5" x-text="kartu.simbol"></span>
                                <div>
                                    <p class="font-semibold mb-0.5"
                                       :class="kartu.terbalik ? 'text-red-300' : 'text-amber-300'"
                                       x-text="kartu.nama + ' ' + (kartu.terbalik ? '{{ __('laporan.form.tarot_terbalik_label') }}' : '{{ __('laporan.form.tarot_tegak_label') }}')"></p>
                                    <p class="text-white/50 leading-relaxed"
                                       x-text="kartu.terbalik ? kartu.makna_terbalik : kartu.makna_tegak"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Tombol ambil kartu --}}
                    <div class="flex gap-2" x-show="!kartusudahDiambil">
                        <button type="button" @click="ambilKartu()"
                            class="flex-1 py-2.5 bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-300 rounded-xl text-sm font-medium transition">
                            {{ __('laporan.form.tarot_pilih_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        {{-- /BONUS TAROT --}}

        <div x-show="errorMsg" class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-300 text-sm mb-4" x-text="errorMsg"></div>

        <button
            type="submit"
            :disabled="terjawab < {{ count($fitur['pertanyaan']) }} || loading || !formReady"
            :class="(terjawab < {{ count($fitur['pertanyaan']) }} || !formReady)
                ? 'opacity-40 cursor-not-allowed'
                : 'hover:from-purple-500 hover:to-violet-500 shadow-lg shadow-purple-900/40'"
            class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-violet-600 rounded-xl font-semibold text-sm tracking-wide transition-all duration-200 disabled:opacity-40"
        >
            <span x-show="!loading">
                <span x-show="!kartusudahDiambil">{{ __('laporan.form.submit') }}</span>
                <span x-show="kartusudahDiambil">{{ __('laporan.form.submit_tarot') }}</span>
            </span>
            <span x-show="loading">{{ __('laporan.form.memproses') }}</span>
        </button>

        <p class="text-center text-white/25 text-xs mt-3">
            {!! __('laporan.form.durasi_note') !!}
        </p>
    </form>

</div>

@push('scripts')
<script>
// Dataset 22 Major Arcana (dipakai client-side untuk random draw)
const TAROT_DECK = @json(\App\Data\TarotData::semua());

function kuesionerForm() {
    return {
        jawaban: {
            @for ($i = 1; $i <= count($fitur['pertanyaan']); $i++)
            @php $oldV = old('q'.$i); @endphp
            q{{ $i }}: {{ $oldV !== null ? ($oldV === '1' ? 'true' : 'false') : 'null' }},
            @endfor
        },
        loading: false,
        errorMsg: '',

        // Tarot state
        tarotAktif: false,
        kartusudahDiambil: false,
        karteTerpilih: [],

        // ── Pasangan gender (feature 5) ──────────────────────────────────────
        @php
            $oldPGender = old('p_jenis_kelamin');
            $autoPGender = $user->jenis_kelamin === 'Laki-laki' ? 'Perempuan' : ($user->jenis_kelamin === 'Perempuan' ? 'Laki-laki' : '');
        @endphp
        pasanganGender: '{{ $oldPGender ?? $autoPGender }}',

        // ── Lokasi pasangan (feature 5) ──────────────────────────────────────
        // ID adalah sumber kebenaran untuk cascading — nama kota/kabupaten bisa kembar
        // (mis. "Bekasi" dipakai oleh Kota Bekasi maupun Kabupaten Bekasi), jadi tidak aman
        // dicocokkan lewat nama saja.
        provinsiList:      [],
        pProvince:         '{{ old('p_province', '') }}',
        pKota:             '{{ old('p_kota', '') }}',
        pKecamatan:        '{{ old('p_kecamatan', '') }}',
        pKelurahan:        '{{ old('p_kelurahan', '') }}',
        pSelectedProvId:   '',
        pSelectedKotaId:   '',
        pSelectedKecId:    '',
        pSelectedKelId:    '',
        pKotaList:         [],
        pKecamatanList:    [],
        pKelurahanList:    [],

        labelKota(k) {
            return (k.tipe === 'Kota' ? 'Kota ' : 'Kab. ') + k.nama;
        },

        get terjawab() {
            return Object.values(this.jawaban).filter(v => v !== null).length;
        },

        // Feature 5: true jika data pasangan sudah cukup terisi
        get formReady() {
            @if($featureId === 5)
            return this.pProvince && this.pKota && this.pKecamatan && this.pKelurahan && this.pasanganGender;
            @else
            return true;
            @endif
        },

        async init() {
            // Restore jawaban yang disimpan sebelum redirect ke tarot page
            const savedJawaban = localStorage.getItem('jawaban_for_{{ $featureId }}');
            if (savedJawaban) {
                try {
                    const j = JSON.parse(savedJawaban);
                    Object.assign(this.jawaban, j);
                    localStorage.removeItem('jawaban_for_{{ $featureId }}');
                } catch(e) {}
            }

            // Cek apakah ada kartu yang sudah dipilih dari halaman /tarot/pilih
            const stored = localStorage.getItem('tarot_for_{{ $featureId }}');
            if (stored) {
                try {
                    const cards = JSON.parse(stored);
                    if (Array.isArray(cards) && cards.length === 3) {
                        this.karteTerpilih     = cards;
                        this.kartusudahDiambil = true;
                        this.tarotAktif        = true;
                        localStorage.removeItem('tarot_for_{{ $featureId }}');
                    }
                } catch(e) {}
            }

            // Load provinsi untuk form pasangan (feature 5)
            @if($featureId === 5)
            const res = await fetch('/api/wilayah/provinsi');
            this.provinsiList = await res.json();

            // Restore lokasi dari old() setelah validation failure
            if (this.pProvince && !this.pSelectedProvId) {
                const prov = this.provinsiList.find(x => x.nama === this.pProvince);
                if (prov) {
                    this.pSelectedProvId = prov.id;
                    await this.loadPKota(prov.id);
                    if (this.pKota) {
                        const kota = this.pKotaList.find(x => this.labelKota(x) === this.pKota);
                        if (kota) {
                            this.pSelectedKotaId = kota.id;
                            await this.loadPKecamatan(kota.id);
                            if (this.pKecamatan) {
                                const kec = this.pKecamatanList.find(x => x.nama === this.pKecamatan);
                                if (kec) {
                                    this.pSelectedKecId = kec.id;
                                    await this.loadPKelurahan(kec.id);
                                    if (this.pKelurahan) {
                                        const kel = this.pKelurahanList.find(x => x.nama === this.pKelurahan);
                                        if (kel) this.pSelectedKelId = kel.id;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Restore data pasangan yang disimpan sebelum redirect ke tarot
            const savedP = localStorage.getItem('pasangan_for_{{ $featureId }}');
            if (savedP) {
                try {
                    const pd = JSON.parse(savedP);
                    localStorage.removeItem('pasangan_for_{{ $featureId }}');
                    this.pProvince       = pd.pProvince       || '';
                    this.pKota           = pd.pKota           || '';
                    this.pKecamatan      = pd.pKecamatan      || '';
                    this.pKelurahan      = pd.pKelurahan      || '';
                    this.pSelectedProvId = pd.pSelectedProvId || '';
                    this.pSelectedKotaId = pd.pSelectedKotaId || '';
                    this.pSelectedKecId  = pd.pSelectedKecId  || '';
                    this.pSelectedKelId  = pd.pSelectedKelId  || '';
                    if (this.pSelectedProvId) await this.loadPKota(this.pSelectedProvId);
                    if (this.pSelectedKotaId) await this.loadPKecamatan(this.pSelectedKotaId);
                    if (this.pSelectedKecId)  await this.loadPKelurahan(this.pSelectedKecId);
                    await this.$nextTick();
                    ['p_nama','p_dob','p_birth_hour','p_agama','p_status_pernikahan',
                     'p_anak_ke','p_jumlah_saudara','p_pekerjaan'].forEach(n => {
                        const el = document.querySelector(`[name="${n}"]`);
                        if (el && pd[n] !== undefined) el.value = pd[n];
                    });
                } catch(e) {}
            }
            @endif
        },

        // ── Cascade lokasi pasangan — helpers (load only, no reset) ─────────
        async loadPKota(provinsiId) {
            const r = await fetch(`/api/wilayah/kota?provinsi_id=${provinsiId}`);
            this.pKotaList = await r.json();
        },

        async loadPKecamatan(kotaId) {
            const r = await fetch(`/api/wilayah/kecamatan?kota_id=${kotaId}`);
            this.pKecamatanList = await r.json();
        },

        async loadPKelurahan(kecamatanId) {
            const r = await fetch(`/api/wilayah/kelurahan?kecamatan_id=${kecamatanId}`);
            this.pKelurahanList = await r.json();
        },

        // ── Cascade handlers (reset child state on manual change) ────────────
        async onPProvinsiChange() {
            const p = this.provinsiList.find(x => x.id == this.pSelectedProvId);
            this.pProvince = p ? p.nama : '';
            this.pSelectedKotaId = ''; this.pSelectedKecId = ''; this.pSelectedKelId = '';
            this.pKota = ''; this.pKecamatan = ''; this.pKelurahan = '';
            this.pKotaList = []; this.pKecamatanList = []; this.pKelurahanList = [];
            if (this.pSelectedProvId) await this.loadPKota(this.pSelectedProvId);
        },

        async onPKotaChange() {
            const k = this.pKotaList.find(x => x.id == this.pSelectedKotaId);
            this.pKota = k ? this.labelKota(k) : '';
            this.pSelectedKecId = ''; this.pSelectedKelId = '';
            this.pKecamatan = ''; this.pKelurahan = '';
            this.pKecamatanList = []; this.pKelurahanList = [];
            if (this.pSelectedKotaId) await this.loadPKecamatan(this.pSelectedKotaId);
        },

        async onPKecamatanChange() {
            const k = this.pKecamatanList.find(x => x.id == this.pSelectedKecId);
            this.pKecamatan = k ? k.nama : '';
            this.pSelectedKelId = '';
            this.pKelurahan = '';
            this.pKelurahanList = [];
            if (this.pSelectedKecId) await this.loadPKelurahan(this.pSelectedKecId);
        },

        onPKelurahanChange() {
            const k = this.pKelurahanList.find(x => x.id == this.pSelectedKelId);
            this.pKelurahan = k ? k.nama : '';
        },

        toggleTarot() {
            if (!this.tarotAktif) {
                // Simpan jawaban sebelum redirect agar tidak hilang
                localStorage.setItem('jawaban_for_{{ $featureId }}', JSON.stringify(this.jawaban));
                @if($featureId === 5)
                localStorage.setItem('pasangan_for_{{ $featureId }}', JSON.stringify({
                    pProvince:           this.pProvince,
                    pKota:               this.pKota,
                    pKecamatan:          this.pKecamatan,
                    pKelurahan:          this.pKelurahan,
                    pSelectedProvId:     this.pSelectedProvId,
                    pSelectedKotaId:     this.pSelectedKotaId,
                    pSelectedKecId:      this.pSelectedKecId,
                    pSelectedKelId:      this.pSelectedKelId,
                    p_nama:              document.querySelector('[name=p_nama]')?.value || '',
                    p_dob:               document.querySelector('[name=p_dob]')?.value || '',
                    p_birth_hour:        document.querySelector('[name=p_birth_hour]')?.value || '',
                    p_agama:             document.querySelector('[name=p_agama]')?.value || '',
                    p_status_pernikahan: document.querySelector('[name=p_status_pernikahan]')?.value || '',
                    p_anak_ke:           document.querySelector('[name=p_anak_ke]')?.value || '',
                    p_jumlah_saudara:    document.querySelector('[name=p_jumlah_saudara]')?.value || '',
                    p_pekerjaan:         document.querySelector('[name=p_pekerjaan]')?.value || '',
                }));
                @endif
                window.location.href = '{{ route("tarot.pilih") }}?back={{ $featureId }}';
            } else {
                this.tarotAktif        = false;
                this.kartusudahDiambil = false;
                this.karteTerpilih     = [];
                localStorage.removeItem('tarot_for_{{ $featureId }}');
            }
        },

        ambilKartu() {
            // Fisher-Yates shuffle, ambil 3 unik
            const deck = Object.entries(TAROT_DECK).map(([id, k]) => ({ id: parseInt(id), ...k }));
            for (let i = deck.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [deck[i], deck[j]] = [deck[j], deck[i]];
            }
            this.karteTerpilih = deck.slice(0, 3).map(k => ({
                ...k,
                terbalik: Math.random() < 0.5,
            }));
            this.kartusudahDiambil = true;
        },

        batalkanTarot() {
            this.kartusudahDiambil = false;
            this.karteTerpilih     = [];
        },

        submitForm(e) {
            const total = {{ count($fitur['pertanyaan']) }};
            if (this.terjawab < total) {
                this.errorMsg = '{{ __('laporan.form.js_error_jawab_semua', ['total' => '__TOTAL__']) }}'.replace('__TOTAL__', total);
                return;
            }
            this.errorMsg = '';
            this.loading  = true;
            e.target.submit();
        }
    }
}
</script>
@endpush
@endsection
