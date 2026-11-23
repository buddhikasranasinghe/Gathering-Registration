@if (session('status'))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 backdrop-blur dark:text-emerald-200">
        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-200">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6 9 17l-5-5" />
            </svg>
        </span>
        <span class="font-medium">{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-800 backdrop-blur dark:text-rose-200">
        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-500/20 text-rose-700 dark:text-rose-200">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 8v5M12 17h.01" />
            </svg>
        </span>
        <ul class="list-disc space-y-1 pl-4 font-medium">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
