<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rumus Langit') — Konsultan Metafisika & Wellness</title>
    <meta name="description" content="@yield('description', 'Platform konsultasi metafisika. Weton, Shio, Zodiak, dan psikologi personal dalam satu laporan naratif yang empatik.')">

    <link rel="icon" type="image/png" href="{{ asset('favicon-32.png') }}" sizes="32x32">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-192.png') }}">
    <meta name="theme-color" content="#060614">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cosmic: {
                            900: '#060614',
                            800: '#0d0d2b',
                            700: '#14143d',
                            600: '#1e1e52',
                            accent: '#7c3aed',
                            gold:   '#f59e0b',
                            teal:   '#06b6d4',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body class="bg-cosmic-900 text-white min-h-screen font-sans antialiased">

    {{-- Starfield background --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none no-print">
        <div class="absolute inset-0" style="background: radial-gradient(ellipse at 20% 50%, rgba(124,58,237,0.08) 0%, transparent 60%), radial-gradient(ellipse at 80% 20%, rgba(6,182,212,0.06) 0%, transparent 50%);"></div>
    </div>

    {{-- Header --}}
    <header class="relative z-10 border-b border-white/5 bg-cosmic-900/80 backdrop-blur-sm">
        <div class="max-w-5xl mx-auto px-4 py-2 flex items-center justify-between">
            <a href="{{ session('user_id') ? route('trial.hasil') : route('trial.index') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="Rumus Langit" class="h-16 w-auto">
                <span class="text-sm font-semibold tracking-wide hidden sm:block">Rumus Langit</span>
            </a>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 text-xs">
                    <a href="{{ route('locale.switch', 'id') }}"
                       class="px-2 py-1 rounded {{ app()->getLocale() === 'id' ? 'bg-white/10 text-white' : 'text-white/40 hover:text-white/60' }}">ID</a>
                    <span class="text-white/20">/</span>
                    <a href="{{ route('locale.switch', 'en') }}"
                       class="px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-white/10 text-white' : 'text-white/40 hover:text-white/60' }}">EN</a>
                </div>
                @if (session('user_id'))
                    @php $namaUser = \App\Models\User::find(session('user_id'))?->name ?? '' @endphp
                    <span class="text-xs text-white/40 hidden sm:block">{{ Str::words($namaUser, 1, '') }}</span>
                    <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="text-xs text-white/30 hover:text-white/60 border border-white/10 hover:border-white/20 rounded-lg px-3 py-1.5 transition">
                            {{ __('nav.logout') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('auth.login.form') }}"
                       class="text-xs text-white/40 hover:text-white/70 border border-white/10 hover:border-white/20 rounded-lg px-3 py-1.5 transition">
                        {{ __('nav.login') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Flash info message --}}
        @if (session('info'))
        <div class="bg-blue-500/10 border-t border-blue-500/20 text-blue-300 text-xs text-center py-2 px-4">
            {{ session('info') }}
        </div>
        @endif
    </header>

    {{-- Main --}}
    <main class="relative z-10">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="relative z-10 border-t border-white/5 mt-16 py-8 text-center text-white/30 text-xs">
        <p>{{ __('nav.footer_copyright', ['year' => date('Y')]) }}</p>
        <p class="mt-1">{{ __('nav.footer_disclaimer') }}</p>
    </footer>

    @stack('scripts')
</body>
</html>
