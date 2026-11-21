<x-layouts.app title="Home">
    <div class="mb-6 rounded-2xl border border-indigo-100 bg-indigo-50 p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Countdown</p>

        @if ($countdown->hasPassed())
            <p class="mt-1 text-lg font-medium text-indigo-900">The function has passed.</p>
        @elseif ($countdown->isToday())
            <p class="mt-1 text-lg font-medium text-indigo-900">The function is today.</p>
        @else
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-bold tabular-nums text-indigo-700">{{ $countdown->remainingDays() }}</span>
                <span class="text-sm font-medium text-indigo-600">{{ $countdown->remainingDays() === 1 ? 'day to go' : 'days to go' }}</span>
            </p>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Welcome, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    You are signed in with a verified email address.
                </p>
            </div>

            <x-ui.role-badge :role="auth()->user()->role" />
        </div>

        <dl class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</dt>
                <dd class="mt-1 break-all text-sm font-medium text-slate-800">{{ auth()->user()->email }}</dd>
            </div>

            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Role</dt>
                <dd class="mt-1 text-sm font-medium text-slate-800">{{ auth()->user()->role->label() }}</dd>
            </div>
        </dl>
    </div>
</x-layouts.app>
