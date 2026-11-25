<x-layouts.app title="Home">
    <section class="glass relative mb-6 overflow-hidden p-6 sm:p-8">
        <span aria-hidden="true" class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-gradient-to-br from-fuchsia-400/40 to-amber-300/40 blur-3xl"></span>
        <span aria-hidden="true" class="pointer-events-none absolute -bottom-24 left-10 h-56 w-56 rounded-full bg-gradient-to-br from-indigo-400/30 to-sky-300/30 blur-3xl"></span>

        <div class="relative">
            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-violet-700 dark:text-violet-300">Countdown</p>

            @if ($countdown->hasPassed())
                <p class="mt-2 font-display text-2xl font-semibold text-slate-900 dark:text-white">The function has passed.</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Thank you for being part of the gathering.</p>
            @elseif ($countdown->isToday())
                <p class="mt-2 font-display text-2xl font-semibold text-slate-900 dark:text-white">The function is today.</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">See you on the dance floor.</p>
            @else
                <p class="mt-2 flex flex-wrap items-baseline gap-3">
                    <span class="animate-pulse-glow font-display text-5xl font-bold tabular-nums text-gradient sm:text-6xl">{{ $countdown->remainingDays() }}</span>
                    <span class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-600 dark:text-slate-300">
                        {{ $countdown->remainingDays() === 1 ? 'day to go' : 'days to go' }}
                    </span>
                </p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">The countdown is on. Get your people together.</p>
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="glass animate-rise p-6 sm:p-8 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <x-ui.avatar :name="auth()->user()->name" size="lg" />

                    <div>
                        <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                            Welcome, {{ auth()->user()->name }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            You are signed in with a verified email address.
                        </p>
                    </div>
                </div>

                <x-ui.role-badge :role="auth()->user()->role" :label="auth()->user()->displayRoleLabel()" />
            </div>

            <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                <div class="glass-soft p-4">
                    <dt class="label">Email</dt>
                    <dd class="mt-1.5 break-all text-sm font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->email }}</dd>
                </div>

                <div class="glass-soft p-4">
                    <dt class="label">Role</dt>
                    <dd class="mt-1.5 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->displayRoleLabel() }}</dd>
                </div>
            </dl>
        </section>

        <section class="glass animate-rise p-6 lg:col-span-1">
            <p class="label">Quick links</p>

            <div class="mt-4 flex flex-col gap-2.5">
                @if (auth()->user()->isAdministrator())
                    <a href="{{ route('participants.index') }}" class="glass-soft flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:text-violet-700 dark:text-slate-200 dark:hover:text-violet-200">
                        <span>Participants</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    <a href="{{ route('treasury.index') }}" class="glass-soft flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:text-violet-700 dark:text-slate-200 dark:hover:text-violet-200">
                        <span>Treasury</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @endif

                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('users.index') }}" class="glass-soft flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:text-violet-700 dark:text-slate-200 dark:hover:text-violet-200">
                        <span>Users</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    <a href="{{ route('batches.index') }}" class="glass-soft flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:text-violet-700 dark:text-slate-200 dark:hover:text-violet-200">
                        <span>Batches</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @endif
            </div>

            <p class="mt-6 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                One gathering, one guest list. Keep the roster tidy and the memories loud.
            </p>
        </section>
    </div>
</x-layouts.app>
