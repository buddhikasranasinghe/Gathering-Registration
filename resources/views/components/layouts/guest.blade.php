<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('theme');
                var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (error) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center px-4 py-10 font-sans text-slate-900 antialiased dark:text-slate-100">
    <div class="event-canvas" aria-hidden="true">
        <span class="orb orb-indigo"></span>
        <span class="orb orb-fuchsia"></span>
        <span class="orb orb-amber"></span>
    </div>

    <button
        type="button"
        data-theme-toggle
        aria-label="Toggle dark mode"
        aria-pressed="false"
        class="fixed right-4 top-4 z-30 flex h-10 w-10 items-center justify-center rounded-xl border border-white/60 bg-white/50 text-slate-600 shadow-sm backdrop-blur transition hover:bg-white/80 hover:text-slate-900 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
    >
        <svg class="theme-icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
        </svg>
        <svg class="theme-icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
    </button>

    <main class="w-full max-w-md animate-rise">
        <div class="mb-8 text-center">
            <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-3">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 font-display text-xl font-bold text-white shadow-xl shadow-violet-500/30">
                    R
                </span>
                <span class="font-display text-3xl font-semibold tracking-tight text-gradient">{{ config('app.name') }}</span>
            </a>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">A gathering to remember.</p>
        </div>

        <div class="glass p-7 sm:p-8">
            {{ $slot }}
        </div>

        <p class="mt-8 text-center text-xs text-slate-500 dark:text-slate-500">
            Passwordless sign-in &middot; One-time email code
        </p>
    </main>

    @stack('scripts')
</body>
</html>
