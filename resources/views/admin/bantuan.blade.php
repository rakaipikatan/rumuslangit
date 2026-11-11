@extends('admin.layout')
@section('title', 'Chat Bantuan')

@section('content')
<div class="mb-5 text-sm text-white/50 max-w-3xl leading-relaxed">
    Percakapan chat bantuan otomatis (7 hari terakhir):
    AI <b class="text-emerald-300">{{ $stat['ai'] ?? 0 }}</b> ·
    FAQ cadangan <b class="text-sky-300">{{ $stat['faq'] ?? 0 }}</b> ·
    Tidak terjawab <b class="text-amber-300">{{ $stat['gagal'] ?? 0 }}</b>.
    Pertanyaan "tidak terjawab" menunjukkan bagian FAQ yang perlu dilengkapi (<code>lang/id/bantuan.php</code>).
</div>
<div class="flex gap-2 mb-4 text-xs">
    <a href="{{ route('admin.bantuan') }}" class="px-3 py-1.5 rounded-lg border {{ $filter ? 'border-white/10 text-white/50' : 'border-white/30 bg-white/10' }}">Semua</a>
    <a href="{{ route('admin.bantuan', ['sumber' => 'gagal']) }}" class="px-3 py-1.5 rounded-lg border {{ $filter === 'gagal' ? 'border-amber-400/50 bg-amber-500/10 text-amber-300' : 'border-white/10 text-white/50' }}">Tidak terjawab</a>
</div>
<div class="space-y-3">
@forelse($logs as $l)
    <div class="border border-white/10 rounded-xl p-4 text-sm">
        <div class="flex items-center gap-2 text-[11px] text-white/40 mb-2">
            <span>{{ $l->created_at->timezone('Asia/Jakarta')->format('d/m H:i') }}</span>
            <span class="px-1.5 py-0.5 rounded {{ $l->sumber === 'ai' ? 'bg-emerald-500/15 text-emerald-300' : ($l->sumber === 'faq' ? 'bg-sky-500/15 text-sky-300' : 'bg-amber-500/15 text-amber-300') }}">{{ $l->sumber }}</span>
            @if($l->user_id)<a href="{{ route('admin.users.detail', $l->user_id) }}" class="underline">user #{{ $l->user_id }}</a>@endif
            @if($l->affiliate_id)<a href="{{ route('admin.affiliates.detail', $l->affiliate_id) }}" class="underline">afiliator #{{ $l->affiliate_id }}</a>@endif
        </div>
        <div class="text-white/85"><b>T:</b> {{ $l->pertanyaan }}</div>
        <div class="text-white/50 mt-1"><b>J:</b> {{ \Illuminate\Support\Str::limit($l->jawaban, 400) }}</div>
    </div>
@empty
    <div class="text-white/40 text-sm py-8 text-center">Belum ada percakapan.</div>
@endforelse
</div>
<div class="mt-5">{{ $logs->links() }}</div>
@endsection
