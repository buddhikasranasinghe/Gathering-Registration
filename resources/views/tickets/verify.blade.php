<x-layouts.guest title="Ticket verification">
    <div class="text-center">
        @if ($valid)
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-300">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m5 13 4 4L19 7" />
                </svg>
            </span>
            <h1 class="mt-4 font-display text-2xl font-semibold">Valid ticket</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">This ticket is active and ready for entry.</p>
        @else
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-300">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </span>
            <h1 class="mt-4 font-display text-2xl font-semibold">Deactivated ticket</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">This ticket has been deactivated and cannot be used.</p>
        @endif

        <div class="mt-6 rounded-xl border border-slate-300/50 bg-slate-50/60 px-4 py-3 dark:border-white/10 dark:bg-white/5">
            <p class="text-xs text-slate-500 dark:text-slate-400">Ticket number</p>
            <p class="mt-1 font-mono text-sm font-semibold">{{ $ticket->ticket_number }}</p>
        </div>
    </div>
</x-layouts.guest>
