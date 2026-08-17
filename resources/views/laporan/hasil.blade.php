@extends('layouts.app')
@section('title', $fitur['nama'] . ' — ' . $user->name)

@push('head')
<style>
/* ═══════════════════════════════════════════
   PRINT / PDF — SLIDE MODE (A4 Dark Slides)
   Setiap .slide = satu halaman A4 penuh
═══════════════════════════════════════════ */

/* @page harus di top-level (tidak boleh nested di dalam @media) */
@page { size: A4 portrait; margin: 0; }

@media print {
    /* 1. Hide screen-only */
    header, footer, nav, .no-print { display: none !important; }

    /* 2. Page setup — zero margins, slides manage own padding */
    html  { background: #060614 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; font-size: 8pt !important; }
    body  { background: #060614 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; margin: 0 !important; padding: 0 !important; }
    .print-container { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. ★ Force all elements to print backgrounds + readable text */
    body *, body *::before, body *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color: rgba(255,255,255,0.85) !important;
        border-color: rgba(255,255,255,0.12) !important;
    }

    /* 4. SLIDE — satu halaman A4 penuh per slide */
    .slide {
        width: 210mm !important;
        height: 297mm !important;
        overflow: hidden !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        display: flex !important;
        flex-direction: column !important;
        padding: 12mm 15mm 8mm 15mm !important;
        box-sizing: border-box !important;
        background: #060614 !important;
        position: relative !important;
    }
    .slide:last-child { page-break-after: avoid !important; break-after: avoid !important; }

    /* 5. Slide mini-header (branding bar) */
    .slide-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border-bottom: 1px solid rgba(124,58,237,0.3) !important;
        padding-bottom: 4mm !important;
        margin-bottom: 5mm !important;
        flex-shrink: 0 !important;
    }
    .slide-header .sh-brand { font-size: 7pt !important; font-weight: 900 !important; color: #f59e0b !important; letter-spacing: 1px !important; }
    .slide-header .sh-meta  { font-size: 6.5pt !important; color: rgba(255,255,255,0.4) !important; text-align: right !important; }

    /* 6. Slide body fills remaining height */
    .slide-body {
        flex: 1 !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 4mm !important;
    }
    /* Setiap child langsung dari slide-body isi ruang yang tersedia secara merata */
    .slide-body > * { flex: 1 !important; min-height: 0 !important; overflow: hidden !important; }
    /* Elemen kompak — jangan di-stretch */
    .slide-body > .ph,
    .slide-body > .phl,
    .slide-body > .afirmasi-box { flex: 0 0 auto !important; }

    /* .pc sebagai child langsung → jadikan flex column agar title kompak, konten isi sisa ruang */
    .slide-body > .pc { display: flex !important; flex-direction: column !important; }
    .slide-body > .pc > .pc-title { flex: 0 0 auto !important; }
    .slide-body > .pc > *:not(.pc-title) { flex: 1 !important; min-height: 0 !important; }

    /* Grid di dalam .pc → isi tinggi yang tersedia */
    .print-grid-2 { align-content: stretch !important; height: 100% !important; }
    .print-grid-3 { align-content: stretch !important; height: 100% !important; }
    .print-grid-2 > *, .print-grid-3 > * { align-self: stretch !important; }

    .slide-body-row {
        display: flex !important;
        gap: 4mm !important;
        flex: 1 !important;
        overflow: hidden !important;
        min-height: 0 !important;
    }
    .slide-body-row > * { flex: 1 !important; min-width: 0 !important; overflow: hidden !important; }
    .slide-body-row > .col-2 { flex: 2 !important; }

    /* 7. Slide page-number footer */
    .slide-footer {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border-top: 1px solid rgba(124,58,237,0.2) !important;
        padding-top: 3mm !important;
        margin-top: auto !important;
        flex-shrink: 0 !important;
        font-size: 6pt !important;
    }
    .slide-footer * { color: rgba(255,255,255,0.3) !important; }

    /* 8. Cover header (slide 1) */
    .ph {
        background: linear-gradient(135deg, #1e1b4b 0%, #2d2b6b 50%, #1e3a6e 100%) !important;
        border: 1px solid rgba(124,58,237,0.4) !important;
        border-radius: 8px !important;
        padding: 12px !important;
        margin-bottom: 0 !important;
        page-break-inside: avoid;
        flex-shrink: 0 !important;
    }
    .ph, .ph * { color: #ffffff !important; }
    .ph .print-only { display: block !important; margin-bottom: 8px; }

    /* 9. Section cards — no bottom margin (slide gap handles it) */
    .pc {
        background: #0d0d2b !important;
        border: 1px solid rgba(124,58,237,0.25) !important;
        border-radius: 6px !important;
        padding: 10px !important;
        margin-bottom: 0 !important;
        page-break-inside: avoid;
        overflow: hidden !important;
    }

    /* 10. Section titles */
    .pc-title {
        color: #a78bfa !important;
        font-weight: 700 !important;
        font-size: 7.5pt !important;
        text-transform: uppercase !important;
        letter-spacing: 0.07em !important;
        border-bottom: 1px solid rgba(124,58,237,0.4) !important;
        padding-bottom: 3px !important;
        margin-bottom: 7px !important;
    }

    /* 11. Highlight / insight box */
    .phl {
        background: rgba(124,58,237,0.15) !important;
        border-left: 3px solid #7c3aed !important;
        padding: 6px 10px !important;
        border-radius: 0 4px 4px 0 !important;
    }
    .phl * { color: #c4b5fd !important; font-style: italic !important; }

    /* 12. Progress bars */
    .print-bar-bg  { background: rgba(124,58,237,0.25) !important; border-radius: 999px; height: 5px; width: 100%; display: block; }
    .print-bar-fill{ background: #7c3aed !important; border-radius: 999px; height: 5px; display: block; }

    /* 13. Badges */
    .print-badge-green  { background: rgba(16,185,129,0.15) !important; color: #6ee7b7 !important; border: 1px solid rgba(16,185,129,0.3) !important; }
    .print-badge-red    { background: rgba(239,68,68,0.15)  !important; color: #fca5a5 !important; border: 1px solid rgba(239,68,68,0.3)  !important; }
    .print-badge-amber  { background: rgba(245,158,11,0.15) !important; color: #fcd34d !important; border: 1px solid rgba(245,158,11,0.3) !important; }
    .print-badge-purple { background: rgba(124,58,237,0.15) !important; color: #c4b5fd !important; border: 1px solid rgba(124,58,237,0.3) !important; }
    .print-badge-blue   { background: rgba(59,130,246,0.15) !important; color: #93c5fd !important; border: 1px solid rgba(59,130,246,0.3)  !important; }

    /* 14. Rekomendasi cards */
    .print-rec-top { background: rgba(124,58,237,0.12) !important; border: 1px solid rgba(124,58,237,0.3) !important; }
    .print-rec-mid { background: rgba(245,158,11,0.08) !important; border: 1px solid rgba(245,158,11,0.25) !important; }
    .print-rec-low { background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(255,255,255,0.1) !important; }

    /* 14b. Tarot section */
    .tarot-card-tegak    { background: rgba(245,158,11,0.1) !important; border: 1px solid rgba(245,158,11,0.3) !important; }
    .tarot-card-terbalik { background: rgba(239,68,68,0.08) !important; border: 1px solid rgba(239,68,68,0.25) !important; }

    /* 15. RUMUS framework */
    .rumus-box {
        background: linear-gradient(135deg, rgba(124,58,237,0.15), rgba(59,130,246,0.1)) !important;
        border: 1px solid rgba(124,58,237,0.35) !important;
    }
    .rumus-box .r-card   { background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(124,58,237,0.25) !important; border-radius: 4px !important; padding: 7px !important; margin-bottom: 5px !important; page-break-inside: avoid; }
    .rumus-box .r-letter { background: rgba(124,58,237,0.3) !important; color: #c4b5fd !important; font-weight: 900 !important; padding: 2px 7px !important; border-radius: 4px !important; margin-right: 6px !important; }
    .rumus-box .r-title  { color: #a78bfa !important; font-weight: 700 !important; font-size: 7.5pt !important; }
    .rumus-box .r-body   { color: rgba(255,255,255,0.8) !important; }

    /* 16. Afirmasi */
    .afirmasi-box {
        background: linear-gradient(135deg, rgba(124,58,237,0.15), rgba(6,182,212,0.08)) !important;
        border: 1px solid rgba(124,58,237,0.4) !important;
        border-radius: 8px !important;
        text-align: center !important;
        padding: 14px !important;
    }
    .afirmasi-box * { color: #e9d5ff !important; font-style: italic !important; }

    /* 17. Grids */
    .print-grid-2 { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 8px !important; }
    .print-grid-3 { display: grid !important; grid-template-columns: 1fr 1fr 1fr !important; gap: 8px !important; }

    /* 18. Legacy .pf footer (now replaced by .slide-footer but kept for compat) */
    .pf { display: none !important; }

    /* 19. Tarot card images in results */
    .tarot-card-img { width: 100% !important; object-fit: cover !important; border-radius: 4px 4px 0 0 !important; display: block !important; max-height: 120px !important; }
}
@media screen { .print-only { display: none !important; } .slide { display: contents; } .slide-header, .slide-footer { display: none !important; } }
</style>
@endpush

@section('content')
@php
    $L    = json_decode($report->response_text, true);
    // Jika gagal decode, coba repair newline literal di dalam string value
    if (!is_array($L)) {
        $L = json_decode(\App\Services\GeminiService::repairJson($report->response_text), true);
    }
    $isJ  = is_array($L);
    $tgl  = \Carbon\Carbon::now()->locale(app()->getLocale())->isoFormat('D MMMM Y');
    $dob  = \Carbon\Carbon::parse($user->dob)->locale(app()->getLocale())->isoFormat('D MMMM Y');
    $skor = $L['skor'] ?? [];
    $nil  = (int)($skor['nilai'] ?? 0);
    $lvl  = $skor['level'] ?? 'cukup';
    $lvlColor = match($lvl) { 'baik' => 'emerald', 'perlu_perhatian' => 'red', default => 'amber' };
    $lvlEmoji = match($lvl) { 'baik' => '🟢', 'perlu_perhatian' => '🔴', default => '🟡' };
    // Deteksi format lama untuk feature 12 (ada skor tapi tidak ada simbol_utama)
    $isMimpiFormatLama = $isJ && $featureId == 12 && empty($L['simbol_utama']) && !empty($L['skor']);
    // Slide mini-header helper snippet
    $slideMetaHtml = e($fitur['nama']) . ' · ' . e($user->name);
@endphp

<div class="max-w-4xl mx-auto px-4 py-8 print-container">

{{-- Breadcrumb --}}
<div class="flex items-center justify-between mb-5 no-print">
    <div class="flex items-center gap-2 text-xs text-white/30">
        <a href="{{ route('trial.katalog') }}" class="hover:text-white/50">{{ __('laporan.hasil.kembali_analisis') }}</a>
        <span>/</span><span>{{ $fitur['hub'] }}</span><span>/</span>
        <span class="text-white/50">{{ $fitur['nama'] }}</span>
    </div>
    <button onclick="downloadPDF(this)"
            class="flex items-center gap-1.5 text-xs text-white/40 hover:text-white/70 border border-white/10 hover:border-white/20 rounded-lg px-3 py-1.5 transition">
        {{ __('laporan.hasil.unduh_pdf') }}
    </button>
</div>

{{-- ═══════════ DISCLAIMER ═══════════ --}}
<div class="mb-5 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-amber-500/20 bg-amber-500/5 px-4 py-2.5 text-xs text-amber-200/60 no-print">
    <span class="font-semibold text-amber-400 shrink-0">{{ __('laporan.hasil.disclaimer_label') }}</span>
    <span>{{ __('laporan.hasil.disclaimer_text') }}</span>
    <a href="https://www.primbon.com" target="_blank" rel="noopener"
       class="text-amber-300/80 underline underline-offset-2 hover:text-amber-300 transition shrink-0">primbon.com</a>
</div>

@if($isJ)

{{-- ═══════════ TAFSIR MIMPI (Feature 12 — layout khusus) ═══════════ --}}
@if($featureId == 12 && $isMimpiFormatLama)

{{-- SLIDE: Error state (no slide wrapper needed, just show inline) --}}
<div class="ph relative bg-gradient-to-br from-cosmic-900 via-cosmic-800 to-cosmic-700 border border-white/10 rounded-2xl p-6 mb-5 overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 15% 50%,rgba(124,58,237,.25) 0%,transparent 55%)"></div>
    <div class="relative">
        <div class="print-only mb-3 flex items-center gap-2">
            <span style="font-size:18px;font-weight:900;color:#f59e0b">✦ RUMUS LANGIT</span>
            <span style="font-size:8pt;color:#9ca3af;letter-spacing:2px">{{ __('laporan.hasil.brand_tagline') }}</span>
        </div>
        <h1 class="text-xl font-bold">{{ $fitur['icon'] }} {{ $fitur['nama'] }}</h1>
        <div class="text-xs text-white/50 mt-1">{{ $user->name }} · {{ $tgl }}</div>
    </div>
</div>
{{-- Format lama: data tidak sesuai template mimpi, minta submit ulang --}}
<div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-6 mb-5 text-center">
    <div class="text-3xl mb-3">⚠️</div>
    <h3 class="text-base font-semibold text-amber-300 mb-2">{{ __('laporan.hasil.data_perlu_update_title') }}</h3>
    <p class="text-sm text-white/60 mb-4">{{ __('laporan.hasil.data_perlu_update_desc') }}</p>
    <a href="{{ route('laporan.form', $featureId) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 text-amber-300 rounded-xl text-sm font-medium transition">
        {{ __('laporan.hasil.submit_ulang_mimpi') }}
    </a>
</div>

@elseif($featureId == 12)

{{-- ══ MIMPI SLIDE 1: Cover + Interpretasi ══ --}}
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        {{-- Cover header --}}
        <div class="ph relative bg-gradient-to-br from-cosmic-900 via-cosmic-800 to-cosmic-700 border border-white/10 rounded-2xl p-6 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 15% 50%,rgba(124,58,237,.25) 0%,transparent 55%)"></div>
            <div class="relative">
                <div class="print-only mb-3 flex items-center gap-2">
                    <span style="font-size:18px;font-weight:900;color:#f59e0b">✦ RUMUS LANGIT</span>
                    <span style="font-size:8pt;color:#9ca3af;letter-spacing:2px">{{ __('laporan.hasil.brand_tagline') }}</span>
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex-1">
                        <div class="text-xs text-white/40 uppercase tracking-widest mb-1">{{ $fitur['hub'] }}</div>
                        <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
                            <span>{{ $fitur['icon'] }}</span><span>{{ $fitur['nama'] }}</span>
                        </h1>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/50">
                            <span>👤 {{ $user->name }}</span>
                            <span>📅 {{ $dob }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-white/10 flex justify-between text-xs text-white/30">
                    <span>{{ $tgl }}</span><span>Rumus Langit</span>
                </div>
            </div>
        </div>

        {{-- Interpretasi --}}
        <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5" style="flex:1;overflow:hidden;">
            <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="text-purple-400">◈</span> {{ __('laporan.hasil.tafsir_mimpi_title') }}
            </div>
            <div class="text-white/80 text-sm leading-relaxed whitespace-pre-line">{{ $L['interpretasi'] ?? '' }}</div>
        </div>

        {{-- Angka Keberuntungan --}}
        @if(!empty($L['angka_keberuntungan']))
        <div class="pc bg-cosmic-800/60 border border-amber-500/20 rounded-2xl p-5">
            <div class="pc-title text-xs font-semibold text-amber-300 uppercase tracking-widest mb-4">{{ __('laporan.hasil.angka_dari_mimpi') }}</div>
            <div class="flex justify-around items-center">
                @foreach($L['angka_keberuntungan'] as $angka)
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-500/30 to-orange-500/20 border border-amber-500/40 flex items-center justify-center">
                        <span class="text-xl font-bold text-amber-300">{{ $angka }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>1</span>
    </div>
</div>

@else

{{-- ══════════════════════════════════════════════
     STANDARD FEATURES (semua fitur selain 12)
══════════════════════════════════════════════ --}}

{{-- ══ SLIDE 1: Cover + Skor + Diagnosis (+ Angka Hoki untuk feature 11) ══ --}}
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        {{-- Cover header --}}
        <div class="ph relative bg-gradient-to-br from-cosmic-900 via-cosmic-800 to-cosmic-700 border border-white/10 rounded-2xl p-6 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 15% 50%,rgba(124,58,237,.25) 0%,transparent 55%)"></div>
            <div class="relative">
                <div class="print-only mb-3 flex items-center gap-2">
                    <span style="font-size:18px;font-weight:900;color:#f59e0b">✦ RUMUS LANGIT</span>
                    <span style="font-size:8pt;color:#9ca3af;letter-spacing:2px">{{ __('laporan.hasil.brand_tagline') }}</span>
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex-1">
                        <div class="text-xs text-white/40 uppercase tracking-widest mb-1">{{ $fitur['hub'] }}</div>
                        <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
                            <span>{{ $fitur['icon'] }}</span><span>{{ $fitur['nama'] }}</span>
                        </h1>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/50">
                            <span>👤 {{ $user->name }}</span>
                            <span>📅 {{ $dob }}</span>
                            <span>🕐 {{ str_pad($user->birth_hour,2,'0',STR_PAD_LEFT) }}.00 WIB</span>
                            <span>📍 {{ $user->kelurahan }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center flex-shrink-0">
                        @foreach([
                            [$kalkulasi['weton']['weton'],'Neptu '.$kalkulasi['weton']['neptu'],'border-purple-400/40 bg-purple-500/15 text-purple-200'],
                            [$kalkulasi['shio']['nama'],'Shio','border-cyan-400/40 bg-cyan-500/15 text-cyan-200'],
                            [$kalkulasi['zodiak'],$kalkulasi['elemen'],'border-amber-400/40 bg-amber-500/15 text-amber-200'],
                        ] as [$v,$s,$c])
                        <div class="border rounded-xl px-2 py-1.5 {{ $c }}">
                            <div class="text-xs font-bold leading-tight">{{ $v }}</div>
                            <div class="text-xs opacity-60">{{ $s }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-white/10 flex justify-between text-xs text-white/30">
                    <span>{{ $tgl }}</span><span>Rumus Langit</span>
                </div>
            </div>
        </div>

        {{-- Feature 11: Angka & Warna Hoki (slide 1, below cover) --}}
        @if($featureId == 11 && (!empty($L['angka_hoki']) || !empty($L['warna_busana'])))
        <div class="pc bg-gradient-to-br from-amber-900/30 to-purple-900/20 border border-amber-500/30 rounded-2xl p-4" style="flex:1;">
            <div class="pc-title text-xs font-semibold text-amber-300 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span>✦</span> {{ __('laporan.hasil.angka_warna_hoki') }}
            </div>
            <div class="slide-body-row">
                @if(!empty($L['angka_hoki']))
                <div>
                    <div class="text-xs text-white/40 uppercase tracking-widest mb-3 text-center">{{ __('laporan.hasil.angka_keberuntungan_3') }}</div>
                    <div class="flex justify-center gap-4">
                        @foreach(array_slice($L['angka_hoki'], 0, 3) as $i => $angka)
                        @php $sizes = ['text-4xl w-20 h-20', 'text-3xl w-16 h-16', 'text-3xl w-16 h-16']; @endphp
                        <div class="flex flex-col items-center gap-2">
                            <div class="{{ $sizes[$i] ?? 'text-3xl w-16 h-16' }} rounded-2xl bg-gradient-to-br from-amber-500/30 to-orange-500/20 border-2 border-amber-400/50 flex items-center justify-center shadow-lg shadow-amber-900/30">
                                <span class="font-black text-amber-300">{{ $angka }}</span>
                            </div>
                            @if($i === 0)<div class="text-xs text-amber-400/60">{{ __('laporan.hasil.utama') }}</div>@endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($L['warna_busana']))
                <div>
                    <div class="text-xs text-white/40 uppercase tracking-widest mb-3 text-center">{{ __('laporan.hasil.warna_busana_2') }}</div>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(array_slice($L['warna_busana'], 0, 2) as $w)
                        <div class="flex items-center gap-3 bg-black/20 rounded-xl p-3 border border-white/5">
                            <div class="w-12 h-12 rounded-xl flex-shrink-0 shadow-md border border-white/10"
                                 style="background-color: {{ $w['hex'] ?? '#7c3aed' }}"></div>
                            <div class="min-w-0">
                                <div class="font-semibold text-sm text-white">{{ $w['warna'] ?? '' }}</div>
                                <div class="text-xs text-white/40 leading-snug mt-0.5">{{ $w['makna'] ?? '' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Skor + Diagnosis (2-column) --}}
        <div class="slide-body-row" style="flex:1;">
            {{-- Skor --}}
            <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5">
                <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-3 flex items-center gap-2">
                    <span class="{{ 'text-'.$lvlColor.'-400' }}">◈</span> {{ __('laporan.hasil.skor_kondisi') }}
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-white mb-1">{{ $nil }}<span class="text-lg text-white/30">/100</span></div>
                    <div class="text-sm font-medium mb-3 {{ 'text-'.$lvlColor.'-300' }}">{{ $lvlEmoji }} {{ $skor['kategori'] ?? '' }}</div>
                    <div class="print-bar-bg h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="print-bar-fill h-full rounded-full {{ 'bg-'.$lvlColor.'-500' }} transition-all" style="width:{{ $nil }}%"></div>
                    </div>
                    <p class="text-xs text-white/40 mt-2 leading-relaxed">{{ $skor['deskripsi'] ?? '' }}</p>
                </div>
            </div>

            {{-- Diagnosis --}}
            <div class="col-2 pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5">
                <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-3 flex items-center gap-2">
                    <span class="text-purple-400">◈</span> {{ __('laporan.hasil.diagnosis_kondisi') }}
                </div>
                <p class="text-white/80 text-sm leading-relaxed mb-3">{{ $L['diagnosis']['kondisi_utama'] ?? '' }}</p>
                <div class="print-grid-2 grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs text-emerald-300 font-semibold mb-1.5">{{ __('laporan.hasil.terdeteksi_positif') }}</div>
                        @foreach($L['diagnosis']['indikator_positif'] ?? [] as $ip)
                        <div class="text-xs text-white/60 flex items-start gap-1.5 mb-1">
                            <span class="text-emerald-400 mt-0.5 flex-shrink-0">●</span>{{ $ip }}
                        </div>
                        @endforeach
                    </div>
                    <div>
                        <div class="text-xs text-red-300 font-semibold mb-1.5">{{ __('laporan.hasil.perlu_perhatian') }}</div>
                        @foreach($L['diagnosis']['indikator_negatif'] ?? [] as $in)
                        <div class="text-xs text-white/60 flex items-start gap-1.5 mb-1">
                            <span class="text-red-400 mt-0.5 flex-shrink-0">●</span>{{ $in }}
                        </div>
                        @endforeach
                    </div>
                </div>
                @if(!empty($L['diagnosis']['kesimpulan']))
                <div class="phl mt-3 bg-purple-500/10 border-l-4 border-purple-500 rounded-r-xl px-3 py-2">
                    <p class="text-purple-300 text-xs italic">💡 {{ $L['diagnosis']['kesimpulan'] }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>1</span>
    </div>
</div>

{{-- ══ SLIDE 2: Analisis Sumber + Hambatan ══ --}}
@if(!empty($L['analisis_sumber']) || !empty($L['hambatan']))
@php $as = $L['analisis_sumber'] ?? []; $hb = $L['hambatan'] ?? []; @endphp
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        @if(!empty($as))
        <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5" style="flex:1;">
            <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="text-cyan-400">◈</span> {{ __('laporan.hasil.analisis_sumber_potensi') }}
            </div>
            <div class="print-grid-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <div class="text-center mb-3">
                        <div class="text-3xl font-bold text-cyan-300">{{ $as['persentase_dominan'] ?? 0 }}%</div>
                        <div class="text-xs text-white/40">{{ __('laporan.hasil.potensi_via_jalur') }}</div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-xs text-emerald-300 font-semibold mb-1">{{ __('laporan.hasil.jalur_kekuatan') }}</div>
                        @foreach($as['jalur_kekuatan'] ?? [] as $jk)
                        <div class="flex items-center gap-2 text-xs text-white/70">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>{{ $jk }}
                        </div>
                        @endforeach
                        <div class="text-xs text-red-300 font-semibold mt-3 mb-1">{{ __('laporan.hasil.jalur_lemah') }}</div>
                        @foreach($as['jalur_lemah'] ?? [] as $jl)
                        <div class="flex items-center gap-2 text-xs text-white/50">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400/60 flex-shrink-0"></span>{{ $jl }}
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="sm:col-span-2 text-white/75 text-sm leading-relaxed whitespace-pre-line">{{ $as['narasi'] ?? '' }}</div>
            </div>
        </div>
        @endif

        @if(!empty($hb))
        <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5" style="flex:1;">
            <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="text-red-400">◈</span> {{ __('laporan.hasil.analisis_hambatan') }}
            </div>
            <div class="print-grid-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach([
                    [__('laporan.hasil.hambatan_internal'),'internal','bg-red-500/10 border-red-500/20','text-red-300','🧠'],
                    [__('laporan.hasil.hambatan_eksternal'),'eksternal','bg-amber-500/10 border-amber-500/20','text-amber-300','🌍'],
                ] as [$label,$key,$bg,$tc,$em])
                @if(!empty($hb[$key]))
                <div class="border rounded-xl p-4 {{ $bg }}">
                    <div class="text-xs font-semibold {{ $tc }} mb-2">{{ $em }} {{ $label }}</div>
                    @foreach($hb[$key] as $h)
                    <div class="text-xs text-white/65 flex items-start gap-1.5 mb-1.5">
                        <span class="{{ $tc }} mt-0.5 flex-shrink-0">▸</span>{{ $h }}
                    </div>
                    @endforeach
                </div>
                @endif
                @endforeach
                @if(!empty($hb['siklus_energi']))
                <div class="border border-blue-500/20 bg-blue-500/10 rounded-xl p-4">
                    <div class="text-xs font-semibold text-blue-300 mb-2">{{ __('laporan.hasil.siklus_energi') }}</div>
                    <p class="text-xs text-white/65 leading-relaxed">{{ $hb['siklus_energi'] }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>2</span>
    </div>
</div>
@endif

{{-- ══ SLIDE 3: Peta Aksi + Zona Hati-Hati ══ --}}
@if(!empty($L['peta_aksi']))
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5" style="flex:1;overflow:hidden;">
            <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="text-emerald-400">◈</span> {{ __('laporan.hasil.peta_aksi_terbaik') }}
                @if(!empty($L['zona_hati_hati']))
                <span class="ml-auto text-xs text-red-400 font-medium">{{ __('laporan.hasil.zona_hati_hati') }}</span>
                @endif
            </div>
            <div class="space-y-3">
                @foreach($L['peta_aksi'] as $pa)
                @php
                    $medals = ['🥇','🥈','🥉'];
                    $medal  = $medals[$pa['ranking']-1] ?? "#{$pa['ranking']}";
                    $pct    = (int)($pa['skor_potensi'] ?? 0);
                    $barW   = $pct;
                    $barColor = $pct >= 80 ? 'bg-emerald-500' : ($pct >= 65 ? 'bg-amber-500' : 'bg-blue-500');
                @endphp
                <div class="border border-white/10 rounded-xl p-4">
                    <div class="flex items-start gap-3 mb-2">
                        <span class="text-xl flex-shrink-0">{{ $medal }}</span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <h4 class="font-semibold text-sm text-white">{{ $pa['label'] }}</h4>
                                <span class="text-sm font-bold text-emerald-300 flex-shrink-0">{{ $pct }}%</span>
                            </div>
                            <div class="print-bar-bg h-1.5 bg-white/10 rounded-full mb-2">
                                <div class="print-bar-fill h-full rounded-full {{ $barColor }}" style="width:{{ $barW }}%"></div>
                            </div>
                            <p class="text-xs text-white/55 leading-relaxed mb-2">{{ $pa['deskripsi'] ?? '' }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($pa['cocok_untuk'] ?? [] as $cf)
                                <span class="text-xs bg-white/5 border border-white/10 text-white/50 rounded-full px-2.5 py-0.5">{{ $cf }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                @foreach($L['zona_hati_hati'] ?? [] as $zh)
                <div class="border border-red-500/20 bg-red-500/5 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">⚠️</span>
                        <div class="flex-1 flex items-center justify-between">
                            <span class="font-semibold text-sm text-red-200">{{ $zh['label'] }}</span>
                            <span class="text-sm font-bold text-red-400">{{ $zh['skor_risiko'] ?? '' }}% {{ __('laporan.hasil.risiko') }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5 pl-8">
                        @foreach($zh['alasan'] ?? [] as $al)
                        <span class="text-xs bg-red-500/10 border border-red-500/20 text-red-300 rounded-full px-2.5 py-0.5">{{ $al }}</span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>3</span>
    </div>
</div>
@endif

{{-- ══ SLIDE 4: Skenario + Rekomendasi ══ --}}
@if(!empty($L['skenario']) || !empty($L['rekomendasi']))
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        @if(!empty($L['skenario']))
        <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5">
            <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="text-blue-400">◈</span> {{ __('laporan.hasil.simulasi_skenario') }}
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($L['skenario'] as $i => $sk)
                @php
                    $skColors = ['border-white/10 bg-white/5','border-amber-500/20 bg-amber-500/5','border-emerald-500/20 bg-emerald-500/10'];
                    $skTC     = ['text-white/60','text-amber-300','text-emerald-300'];
                @endphp
                <div class="border rounded-xl p-4 {{ $skColors[$i] ?? $skColors[0] }}">
                    <div class="text-xs font-semibold {{ $skTC[$i] ?? '' }} mb-2 leading-tight">{{ $sk['kondisi'] }}</div>
                    <div class="text-2xl font-bold text-white my-1">
                        +{{ $sk['proyeksi_min'] ?? 0 }}%–{{ $sk['proyeksi_max'] ?? 0 }}%
                    </div>
                    <p class="text-xs text-white/50 leading-relaxed">{{ $sk['keterangan'] ?? '' }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($L['rekomendasi']))
        <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5" style="flex:1;overflow:hidden;">
            <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="text-purple-400">◈</span> {{ __('laporan.hasil.rekomendasi_praktis') }}
            </div>
            <div class="space-y-3">
                @foreach(array_slice($L['rekomendasi'], 0, 3) as $rek)
                @php
                    $stars = (int)($rek['impact_bintang'] ?? 3);
                    $recBg = $stars >= 5 ? 'border-purple-500/30 bg-purple-500/10' : ($stars >= 4 ? 'border-amber-500/20 bg-amber-500/5' : 'border-white/10 bg-white/5');
                @endphp
                <div class="border rounded-xl p-4 {{ $recBg }}">
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-white/10 text-white/60 text-xs flex items-center justify-center font-bold flex-shrink-0 mt-0.5">
                            {{ $rek['prioritas'] }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h4 class="font-semibold text-sm text-white leading-tight">{{ $rek['aksi'] }}</h4>
                                <span class="flex-shrink-0 text-amber-400 text-sm">
                                    @for($s=1;$s<=5;$s++){{ $s<=$stars ? '★' : '☆' }}@endfor
                                </span>
                            </div>
                            <p class="text-xs text-white/60 leading-relaxed mb-1.5">{{ $rek['penjelasan'] ?? '' }}</p>
                            @if(!empty($rek['timing']))
                            <div class="inline-flex items-center gap-1 text-xs text-blue-300 bg-blue-500/10 border border-blue-500/20 rounded-full px-2.5 py-0.5">
                                🕐 {{ $rek['timing'] }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>4</span>
    </div>
</div>
@endif

{{-- ══ SLIDE 5: Forecast 90 Hari ══ --}}
@if(!empty($L['forecast_90_hari']))
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        <div class="pc bg-cosmic-800/60 border border-white/10 rounded-2xl p-5" style="flex:1;">
            <div class="pc-title text-xs font-semibold text-white/50 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span class="text-amber-400">◈</span> {{ __('laporan.hasil.prediksi_90_hari') }}
            </div>
            <div class="print-grid-3 grid grid-cols-3 gap-4" style="flex:1;">
                @foreach($L['forecast_90_hari'] as $fc)
                @php
                    $peluang  = strtolower($fc['peluang'] ?? 'sedang');
                    $pColors  = ['tinggi'=>'text-emerald-300 bg-emerald-500/10 border-emerald-500/20','sedang'=>'text-amber-300 bg-amber-500/10 border-amber-500/20','rendah'=>'text-red-300 bg-red-500/10 border-red-500/20'];
                    $pClass   = $pColors[$peluang] ?? $pColors['sedang'];
                @endphp
                <div class="border border-white/10 rounded-xl p-5 flex flex-col gap-3">
                    <div class="text-xs font-bold text-white/40 uppercase">{{ $fc['periode'] }}</div>
                    <div class="font-semibold text-sm text-white leading-tight">{{ $fc['tema'] }}</div>
                    <span class="self-start text-xs border rounded-full px-2 py-0.5 {{ $pClass }}">
                        {{ __('laporan.hasil.peluang', ['level' => ucfirst($peluang)]) }}
                    </span>
                    <div class="text-xs text-white/50 leading-relaxed">
                        <span class="text-red-300">⚠ </span>{{ $fc['risiko_utama'] }}
                    </div>
                    <div class="text-xs text-emerald-300 leading-relaxed">
                        <span>→ </span>{{ $fc['fokus_aksi'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>5</span>
    </div>
</div>
@endif

{{-- ══ SLIDE 6: RUMUS Framework + Afirmasi ══ --}}
@if(!empty($L['rumus_framework']) || !empty($L['afirmasi']))
@php $rf = $L['rumus_framework'] ?? []; @endphp
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        @if(!empty($rf))
        <div class="pc rumus-box bg-gradient-to-br from-purple-500/10 to-blue-500/10 border border-purple-500/20 rounded-2xl p-5" style="flex:1;overflow:hidden;">
            <div class="pc-title text-xs font-semibold text-purple-300 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span>✦</span> {{ __('laporan.hasil.rumus_framework_summary') }}
            </div>
            <div class="space-y-3">
                @foreach([
                    ['R',__('laporan.hasil.root_cause'),__('laporan.hasil.root_cause_sub'),'root_cause','bg-red-500/10 border-red-500/20 text-red-300'],
                    ['U',__('laporan.hasil.unlock_direction'),__('laporan.hasil.unlock_direction_sub'),'unlock_direction','bg-emerald-500/10 border-emerald-500/20 text-emerald-300'],
                    ['M',__('laporan.hasil.momentum_window'),__('laporan.hasil.momentum_window_sub'),'momentum_window','bg-blue-500/10 border-blue-500/20 text-blue-300'],
                    ['U',__('laporan.hasil.upgrade_action'),__('laporan.hasil.upgrade_action_sub'),'upgrade_action','bg-amber-500/10 border-amber-500/20 text-amber-300'],
                    ['S',__('laporan.hasil.success_forecast'),__('laporan.hasil.success_forecast_sub'),'success_forecast','bg-purple-500/10 border-purple-500/20 text-purple-300'],
                ] as [$letter,$title,$sub,$key,$cls])
                @if(!empty($rf[$key]))
                <div class="r-card flex items-start gap-3">
                    <div class="r-letter w-7 h-7 rounded-lg border flex items-center justify-center font-bold text-sm flex-shrink-0 {{ $cls }}">
                        {{ $letter }}
                    </div>
                    <div class="flex-1">
                        <div class="r-title text-xs font-semibold text-white/50 mb-0.5">{{ $title }} <span class="text-white/25 font-normal">· {{ $sub }}</span></div>
                        <p class="r-body text-sm text-white/80 leading-relaxed">{{ $rf[$key] }}</p>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($L['afirmasi']))
        <div class="afirmasi-box relative bg-gradient-to-br from-purple-500/15 to-cyan-500/10 border border-purple-500/30 rounded-2xl p-6 text-center overflow-hidden">
            <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 50% 0%,rgba(124,58,237,.12) 0%,transparent 70%)"></div>
            <div class="relative">
                <div class="text-2xl mb-3">✦</div>
                <p class="text-white/80 text-sm leading-relaxed italic max-w-lg mx-auto">"{{ $L['afirmasi'] }}"</p>
            </div>
        </div>
        @endif
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>6</span>
    </div>
</div>
@endif

{{-- ══ SLIDE 7: Bacaan Tarot (opsional) ══ --}}
@if(!empty($L['tarot_reading']))
@php
    $tr = $L['tarot_reading'];
    $tarotImageMap = collect(\App\Data\TarotData::semua())->keyBy('nama')->map(fn($c) => $c['image'] ?? null)->toArray();
@endphp
<div class="slide">
    <div class="slide-header">
        <span class="sh-brand">✦ RUMUS LANGIT</span>
        <span class="sh-meta">{{ $fitur['nama'] }} · {{ $user->name }}</span>
    </div>
    <div class="slide-body">
        <div class="pc bg-cosmic-800/60 border border-amber-500/20 rounded-2xl p-5" style="flex:1;overflow:hidden;">
            <div class="pc-title text-xs font-semibold text-amber-300 uppercase tracking-widest mb-4 flex items-center gap-2">
                <span>🃏</span> {{ __('laporan.hasil.bacaan_tarot_title') }}
            </div>
            @if(!empty($tr['intro']))
            <p class="text-white/60 text-sm italic mb-4">{{ $tr['intro'] }}</p>
            @endif
            @if(!empty($tr['kartu']))
            <div class="print-grid-3 grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                @foreach($tr['kartu'] as $ki => $krt)
                @php
                    $isTerbalik  = strtolower($krt['orientasi'] ?? '') === 'terbalik';
                    $cardBorder  = $isTerbalik ? 'border-red-500/30 bg-red-500/5' : 'border-amber-500/30 bg-amber-500/5';
                    $cardBadge   = $isTerbalik
                        ? 'text-red-300 bg-red-500/10 border-red-500/20'
                        : 'text-amber-300 bg-amber-500/10 border-amber-500/20';
                    $posLabel    = $krt['posisi'] ?? __('laporan.hasil.kartu_n', ['n' => $ki + 1]);
                    $krtNama     = trim(explode(' — ', $krt['nama'] ?? '')[0]);
                    $cardImg     = $tarotImageMap[$krtNama] ?? ($tarotImageMap[$krt['nama'] ?? ''] ?? null);
                @endphp
                <div class="border rounded-xl overflow-hidden {{ $cardBorder }}">
                    @if($cardImg)
                    <div class="relative w-full" style="overflow:hidden;max-height:140px;">
                        <img src="{{ $cardImg }}" alt="{{ $krtNama }}"
                             class="tarot-card-img w-full object-cover {{ $isTerbalik ? 'rotate-180' : '' }}"
                             style="display:block;width:100%;max-height:140px;object-fit:cover;">
                    </div>
                    @endif
                    <div class="p-3">
                        <div class="text-xs text-white/35 uppercase tracking-widest mb-1">{{ $posLabel }}</div>
                        <div class="font-semibold text-sm text-white/90 leading-tight mb-2">{{ $krt['nama'] ?? '' }}</div>
                        <span class="inline-flex items-center text-xs border rounded-full px-2.5 py-0.5 mb-2 {{ $cardBadge }}">
                            {{ $isTerbalik ? __('laporan.hasil.tarot_terbalik') : __('laporan.hasil.tarot_tegak') }}
                        </span>
                        <p class="text-xs text-white/60 leading-relaxed">{{ $krt['interpretasi'] ?? '' }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            @if(!empty($tr['pesan_tarot']))
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3">
                <p class="text-amber-200 text-sm leading-relaxed italic">✦ {{ $tr['pesan_tarot'] }}</p>
            </div>
            @endif
        </div>
    </div>
    <div class="slide-footer">
        <span>{{ __('laporan.hasil.rumus_langit_footer') }}</span>
        <span>{{ $user->name }} · {{ $tgl }}</span>
        <span>7</span>
    </div>
</div>
@endif

@endif {{-- /if featureId == 12 --}}

@else
{{-- Fallback plain text --}}
<div class="bg-cosmic-800/60 border border-white/10 rounded-2xl p-6 mb-6">
    <p class="text-white/75 text-sm leading-relaxed whitespace-pre-line">{{ $report->response_text }}</p>
</div>
@endif

{{-- Screen actions (no-print) --}}
<div class="no-print flex flex-col sm:flex-row gap-3 mt-6">
    <a href="{{ route('trial.katalog') }}" class="flex-1 text-center py-3 bg-cosmic-700/60 hover:bg-cosmic-700 border border-white/10 rounded-xl text-sm font-medium transition">
        {{ __('laporan.hasil.kembali_katalog') }}
    </a>
    <button onclick="downloadPDF(this)" class="flex-1 py-3 bg-gradient-to-r from-purple-600/60 to-violet-600/60 hover:from-purple-600 hover:to-violet-600 border border-purple-500/30 rounded-xl text-sm font-medium transition">
        {{ __('laporan.hasil.unduh_pdf') }}
    </button>
</div>

{{-- Upsell --}}
<div class="no-print border border-white/5 rounded-2xl p-5 mt-5">
    <h3 class="text-xs font-semibold text-white/40 uppercase tracking-wider mb-4">{{ __('laporan.hasil.analisis_lainnya') }}</h3>
    <div class="print-grid-2 grid grid-cols-2 gap-3">
        @foreach(collect(\App\Data\FiturData::semua())->except($featureId)->filter(fn($f)=>empty($f['subscriber_only'])&&empty($f['freemium']))->take(4) as $id => $f)
        <a href="{{ route('laporan.form', $id) }}" class="flex items-start gap-2.5 bg-cosmic-800/40 hover:bg-cosmic-700/40 border border-white/5 hover:border-purple-500/20 rounded-xl p-3 transition group">
            <span class="text-xl mt-0.5">{{ $f['icon'] }}</span>
            <div>
                <div class="text-xs font-medium text-white/70 group-hover:text-white transition leading-tight">{{ $f['nama'] }}</div>
                <div class="text-xs text-white/25 mt-0.5">Rp {{ number_format($f['harga'],0,',','.') }}</div>
            </div>
        </a>
        @endforeach
    </div>
</div>

</div>

@push('scripts')
<script>
function _loadScript(src) {
    return new Promise((res, rej) => {
        if (document.querySelector('script[src="'+src+'"]')) { res(); return; }
        const s = document.createElement('script');
        s.src = src; s.onload = res; s.onerror = rej;
        document.head.appendChild(s);
    });
}

async function downloadPDF(btn) {
    const origLabel = btn.innerHTML;
    btn.innerHTML = '{{ __('laporan.hasil.membuat_pdf') }}';
    btn.disabled = true;

    /* Loading overlay — hides the temporary layout change from user */
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:#060614;z-index:99999;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;';
    overlay.innerHTML = '<div style="font-size:28px;">📄</div><div style="color:rgba(255,255,255,.6);font-size:13px;letter-spacing:.5px;">{{ __('laporan.hasil.membuat_pdf_tunggu') }}</div>';
    document.body.appendChild(overlay);

    try {
        await _loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
        await _loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');

        /* ── Inject PDF-mode CSS ──────────────────────────────────────────────
           These rules override @media screen rules so slides are visible &
           properly laid out when captured in-place by html2canvas.
           Later declarations win when specificity is equal + both !important. */
        const pdfCss = document.createElement('style');
        pdfCss.textContent = `
            body > *:not(#__pdf_overlay) { overflow: visible !important; }
            header, footer { display: none !important; }
            .no-print, .pf { display: none !important; }
            .print-only    { display: block !important; }
            /* Remove container padding so slide never overflows parent edge */
            .print-container { padding: 0 !important; max-width: none !important; }
            .slide {
                display: flex !important; flex-direction: column !important;
                width: 794px !important; height: auto !important;
                min-height: 0 !important; overflow: hidden !important;
                background: #060614 !important;
                padding: 45px 57px 30px 57px !important;
                box-sizing: border-box !important;
                margin: 0 !important; position: static !important;
            }
            .slide-header {
                display: flex !important; justify-content: space-between;
                align-items: center; border-bottom: 1px solid rgba(124,58,237,.3);
                padding-bottom: 15px; margin-bottom: 19px; flex-shrink: 0;
            }
            .slide-body {
                display: flex !important; flex-direction: column;
                gap: 14px; flex: 1 1 auto; overflow: visible;
            }
            .slide-body > * { flex: 0 0 auto !important; }
            .slide-footer {
                display: flex !important; justify-content: space-between;
                align-items: center; border-top: 1px solid rgba(124,58,237,.2);
                padding-top: 11px; margin-top: 24px; flex-shrink: 0;
                font-size: 8px; color: rgba(255,255,255,.4);
            }
            .sh-brand { font-size: 9px !important; font-weight: 900 !important; color: #f59e0b !important; }
            .sh-meta  { font-size: 8px !important; color: rgba(255,255,255,.4) !important; text-align: right; }
            .ph  { background: linear-gradient(135deg,#1e1b4b,#2d2b6b,#1e3a6e) !important; border: 1px solid rgba(124,58,237,.4) !important; border-radius: 8px !important; padding: 12px !important; }
            .pc  { background: #0d0d2b !important; border: 1px solid rgba(124,58,237,.25) !important; border-radius: 6px !important; padding: 10px !important; }
            .phl { background: linear-gradient(135deg,#1a1040,#1e1b4b) !important; border: 1px solid rgba(124,58,237,.3) !important; border-radius: 8px !important; padding: 12px !important; }
            .pc-title { color: #a78bfa !important; font-weight: 700 !important; font-size: 9px !important; text-transform: uppercase; letter-spacing: .07em; border-bottom: 1px solid rgba(124,58,237,.4); padding-bottom: 3px; margin-bottom: 7px; display: block !important; }
        `;
        document.head.appendChild(pdfCss);

        /* Allow CSS to apply & reflow */
        await new Promise(r => setTimeout(r, 300));

        const { jsPDF } = window.jspdf;
        const slides = Array.from(document.querySelectorAll('.slide'));
        const A4W = 210; /* mm */
        const canvases = [];

        /* Use actual viewport width so Tailwind responsive classes compute
           correctly, and so the 794px slide fits without overflowing the container */
        const vw = window.innerWidth || document.documentElement.clientWidth || 1440;

        for (const slide of slides) {
            /* Scroll slide into viewport so html2canvas can capture it */
            slide.scrollIntoView({ behavior: 'instant', block: 'start' });
            await new Promise(r => setTimeout(r, 150));

            /* Measure after reflow to get exact content height */
            const rect = slide.getBoundingClientRect();

            const canvas = await html2canvas(slide, {
                scale           : 2,
                useCORS         : true,
                allowTaint      : true,
                backgroundColor : '#060614',
                logging         : false,
                windowWidth     : vw,
                /* Explicit dimensions: prevents html2canvas from capturing
                   any area outside the slide's intended 794px width, and
                   clamps height to the actual rendered content height */
                width           : 794,
                height          : Math.ceil(rect.height),
            });
            canvases.push(canvas);
        }

        /* ── Restore page ── */
        pdfCss.remove();
        window.scrollTo({ top: 0, behavior: 'instant' });
        overlay.remove();

        /* ── Build PDF: each page = content height, zero empty space ── */
        const firstH = (canvases[0].height / 2) / 794 * A4W;
        const pdf = new jsPDF({ unit: 'mm', format: [A4W, firstH], orientation: 'portrait' });

        canvases.forEach((cv, i) => {
            const imgH = (cv.height / 2) / 794 * A4W;
            if (i > 0) pdf.addPage([A4W, imgH]);
            pdf.addImage(cv.toDataURL('image/jpeg', 0.88), 'JPEG', 0, 0, A4W, imgH);
        });

        const fileName = '{{ Str::slug($fitur["nama"] . " " . $user->name) }}' || 'Rumus-Langit';
        pdf.save(fileName + '.pdf');

    } catch (err) {
        console.error('PDF error:', err);
        overlay.remove();
        alert('{{ __('laporan.hasil.gagal_pdf') }}');
    }

    btn.innerHTML = origLabel;
    btn.disabled = false;
}
</script>
@endpush

@endsection
