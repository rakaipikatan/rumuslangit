@extends('admin.layout')
@section('title', 'Manajemen Users')

@section('content')

{{-- Kredensial user testing yang baru dibuat --}}
@if (session('test_user_created'))
<div x-data="{ show: true }" x-show="show"
     class="mb-5 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-emerald-300 font-semibold text-sm mb-2">✓ User Testing Berhasil Dibuat</div>
            <div class="bg-black/30 rounded-xl px-4 py-3 font-mono text-sm text-white/80 space-y-1">
                @foreach (explode(' | ', session('test_user_created')) as $line)
                <div>{{ $line }}</div>
                @endforeach
            </div>
            <p class="text-xs text-white/30 mt-2">Simpan kredensial ini sebelum menutup halaman.</p>
        </div>
        <button @click="show=false" class="text-white/30 hover:text-white/60 text-lg leading-none">✕</button>
    </div>
</div>
@endif

{{-- Password baru hasil reset --}}
@if (session('password_reset_info'))
<div x-data="{ show: true }" x-show="show"
     class="mb-5 bg-amber-500/10 border border-amber-500/30 rounded-2xl p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-amber-300 font-semibold text-sm mb-2">✓ Password Berhasil Direset</div>
            <div class="bg-black/30 rounded-xl px-4 py-3 font-mono text-sm text-white/80 space-y-1">
                @foreach (explode(' | ', session('password_reset_info')) as $line)
                <div>{{ $line }}</div>
                @endforeach
            </div>
            <p class="text-xs text-white/30 mt-2">Sampaikan password baru ini ke user secara aman, lalu tutup pesan ini.</p>
        </div>
        <button @click="show=false" class="text-white/30 hover:text-white/60 text-lg leading-none">✕</button>
    </div>
</div>
@endif

{{-- Filter + Tombol Buat User Testing --}}
<div class="flex flex-wrap gap-3 mb-5">
    <form method="GET" class="flex gap-2 flex-1">
        <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama atau email…"
            class="flex-1 bg-cosmic-800/60 border border-white/10 rounded-xl px-4 py-2 text-sm text-white placeholder-white/30 focus:outline-none focus:border-purple-500/50 min-w-0">
        <select name="status" class="bg-cosmic-800/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none [color-scheme:dark]">
            <option value="">Semua Status</option>
            <option value="free"     {{ request('status')==='free'     ? 'selected':'' }}>Free</option>
            <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>Subscriber Aktif</option>
            <option value="expired"  {{ request('status')==='expired'  ? 'selected':'' }}>Expired</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm text-white transition">Cari</button>
        <a href="{{ route('admin.users') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-sm text-white/50 transition">Reset</a>
    </form>
    {{-- Tombol buka modal --}}
    <button onclick="document.getElementById('modal-buat-testing').classList.remove('hidden')"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm text-white transition whitespace-nowrap">
        + Buat User Testing
    </button>
</div>

{{-- Modal Buat User Testing --}}
<div id="modal-buat-testing" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">
    <div class="bg-cosmic-800 border border-white/10 rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-bold text-white">Buat User Testing</h2>
            <button onclick="document.getElementById('modal-buat-testing').classList.add('hidden')"
                    class="text-white/30 hover:text-white/60 text-xl leading-none">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.users.buat-testing') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Nama</label>
                <input type="text" name="name" value="{{ old('name','Test User') }}" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition">
            </div>
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="test@example.com" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition placeholder-white/20">
            </div>
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Password</label>
                <input type="text" name="password" value="{{ old('password','Testing123!') }}" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition">
                <p class="text-xs text-white/25 mt-1">Tulis password yang mudah diingat — akan ditampilkan setelah dibuat.</p>
            </div>
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Jenis Kelamin</label>
                <select name="jenis_kelamin" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark]">
                    <option value="Laki-laki" {{ old('jenis_kelamin') === 'Laki-laki' ? 'selected' : '' }}>♂ Laki-laki</option>
                    <option value="Perempuan" {{ old('jenis_kelamin') === 'Perempuan' ? 'selected' : '' }}>♀ Perempuan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Tanggal Lahir</label>
                <input type="date" name="dob" value="{{ old('dob','1990-01-01') }}"
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark]">
            </div>
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Durasi Subscription</label>
                <select name="durasi"
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-purple-500/60 transition [color-scheme:dark]">
                    <option value="1">1 Bulan</option>
                    <option value="3">3 Bulan</option>
                    <option value="6">6 Bulan</option>
                    <option value="12" selected>12 Bulan</option>
                    <option value="24">24 Bulan</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button"
                    onclick="document.getElementById('modal-buat-testing').classList.add('hidden')"
                    class="flex-1 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-sm text-white/50 transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm text-white font-semibold transition">
                    Buat User
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Reset Password (dipakai bersama semua baris user) --}}
<div id="modal-reset-password" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">
    <div class="bg-cosmic-800 border border-white/10 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-bold text-white">Reset Password — <span id="reset-user-nama" class="text-amber-300"></span></h2>
            <button type="button" onclick="tutupResetPassword()" class="text-white/30 hover:text-white/60 text-xl leading-none">✕</button>
        </div>
        <form id="form-reset-password" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-white/50 uppercase tracking-wider mb-1.5">Password Baru</label>
                <input type="text" name="password" required minlength="6"
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-amber-500/60 transition">
                <p class="text-xs text-white/25 mt-1">Minimal 6 karakter. Password ini akan ditampilkan sekali setelah disimpan.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="tutupResetPassword()"
                    class="flex-1 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-sm text-white/50 transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 bg-amber-600 hover:bg-amber-500 rounded-xl text-sm text-white font-semibold transition">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    function bukaResetPassword(id, nama) {
        document.getElementById('reset-user-nama').innerText = nama;
        document.getElementById('form-reset-password').action = `{{ route('admin.users') }}/${id}/reset-password`;
        document.getElementById('modal-reset-password').classList.remove('hidden');
    }
    function tutupResetPassword() {
        document.getElementById('modal-reset-password').classList.add('hidden');
    }
</script>

<div class="bg-cosmic-800/60 border border-white/8 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-white/40 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">ID</th>
                <th class="px-5 py-3 text-left">Nama</th>
                <th class="px-5 py-3 text-left">Email</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Laporan</th>
                <th class="px-5 py-3 text-left">Daftar</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse ($users as $u)
            <tr class="hover:bg-white/2 transition">
                <td class="px-5 py-3 text-white/30">#{{ $u->id }}</td>
                <td class="px-5 py-3">
                    <div class="font-medium text-white/85">{{ $u->name }}</div>
                    @if ($u->dob)
                    <div class="text-xs text-white/30">{{ \Carbon\Carbon::parse($u->dob)->format('d/m/Y') }}</div>
                    @endif
                </td>
                <td class="px-5 py-3">
                    @if ($u->email)
                    <div class="text-white/70">{{ $u->email }}</div>
                    @if ($u->email_verified_at)
                    <div class="text-xs text-emerald-400/70">✓ Terverifikasi</div>
                    @else
                    <div class="text-xs text-amber-400/70">⚠ Belum verifikasi</div>
                    @endif
                    @else
                    <span class="text-white/25">—</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <span class="badge-pill {{ $u->subscription_status === 'active' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20' : ($u->subscription_status === 'expired' ? 'bg-red-500/15 text-red-300 border border-red-500/20' : 'bg-white/5 text-white/40 border border-white/10') }}">
                        {{ $u->subscription_status }}
                    </span>
                </td>
                <td class="px-5 py-3 text-white/50">{{ $u->aiReports->count() }}</td>
                <td class="px-5 py-3 text-white/30 text-xs">
                    {{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '—' }}
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" onclick="bukaResetPassword({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                class="text-xs text-amber-400/60 hover:text-amber-300 transition">Reset Password</button>
                        <form method="POST" action="{{ route('admin.users.hapus', $u->id) }}"
                              onsubmit="return confirm('Hapus user {{ addslashes($u->name) }}? Semua data terkait akan dihapus.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400/60 hover:text-red-300 transition">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-5 py-10 text-center text-white/30">Tidak ada user ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
