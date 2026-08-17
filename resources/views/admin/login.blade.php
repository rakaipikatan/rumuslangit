<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Login — Rumus Langit</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{cosmic:{'900':'#060614','800':'#0d0d2b','700':'#141438'}}}}}</script>
</head>
<body class="bg-cosmic-900 min-h-screen flex items-center justify-center px-4" style="background:radial-gradient(ellipse at 50% 0%,rgba(124,58,237,.15) 0%,#060614 60%)">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="text-3xl font-bold text-white mb-1">✦ Rumus Langit</div>
        <div class="text-xs text-white/30 uppercase tracking-widest">Admin Panel</div>
    </div>
    <div class="bg-cosmic-800/80 border border-white/10 rounded-2xl p-8">
        @if ($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-300 text-sm mb-5">
            {{ $errors->first() }}
        </div>
        @endif
        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-white/50 mb-1.5 uppercase tracking-wider">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/20 text-sm focus:outline-none focus:border-purple-500/60 transition">
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1.5 uppercase tracking-wider">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-cosmic-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500/60 transition">
            </div>
            <button type="submit"
                class="w-full py-3 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 rounded-xl text-sm font-semibold text-white transition mt-2">
                Masuk
            </button>
        </form>
    </div>
</div>
</body>
</html>
