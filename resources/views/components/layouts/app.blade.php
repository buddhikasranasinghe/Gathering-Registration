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
<body class="min-h-full font-sans text-slate-900 antialiased dark:text-slate-100">
    <div class="event-canvas" aria-hidden="true">
        <span class="orb orb-indigo"></span>
        <span class="orb orb-fuchsia"></span>
        <span class="orb orb-amber"></span>
    </div>

    <header class="glass-bar sticky top-0 z-30">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="group flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 font-display text-sm font-bold text-white shadow-lg shadow-violet-500/30 transition group-hover:scale-105">
                    R
                </span>
                <span class="hidden flex-col leading-none min-[480px]:flex">
                    <span class="font-display text-lg font-semibold tracking-tight text-gradient">{{ config('app.name') }}</span>
                    <span class="mt-1 hidden text-[0.65rem] font-medium uppercase tracking-[0.2em] text-slate-500 sm:block dark:text-slate-400">Gathering</span>
                </span>
            </a>

            <div class="flex items-center gap-1 text-sm sm:gap-1.5">
                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('users.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('users.*')])>
                        Users
                    </a>
                    <a href="{{ route('batches.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('batches.*')])>
                        Batches
                    </a>
                @endif

                @if (auth()->user()->isAdministrator())
                    <a href="{{ route('participants.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('participants.*')])>
                        Participants
                    </a>
                    <a href="{{ route('treasury.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('treasury.*')])>
                        Treasury
                    </a>
                @endif

                <span class="ml-1 hidden items-center gap-2 border-l border-slate-300/60 pl-3 sm:flex dark:border-white/10">
                    <span class="hidden text-slate-600 md:inline dark:text-slate-300">{{ auth()->user()->name }}</span>
                    <x-ui.role-badge :role="auth()->user()->role" :label="auth()->user()->displayRoleLabel()" />
                </span>

                <button
                    type="button"
                    data-theme-toggle
                    aria-label="Toggle dark mode"
                    aria-pressed="false"
                    class="ml-1 flex h-9 w-9 items-center justify-center rounded-xl border border-white/60 bg-white/50 text-slate-600 shadow-sm backdrop-blur transition hover:bg-white/80 hover:text-slate-900 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    <svg class="theme-icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
                    </svg>
                    <svg class="theme-icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                    </svg>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <x-ui.alerts placement="app" />

    <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        {{ $slot }}
    </main>

    <footer class="mx-auto w-full max-w-6xl px-4 pb-10 pt-2 text-center text-xs text-slate-500 sm:px-6 dark:text-slate-500">
        {{ config('app.name') }} &middot; {{ now()->year }} &middot; See you there.
    </footer>
</body>
</html>
