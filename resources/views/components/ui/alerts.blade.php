@props(['placement' => 'app'])

@php
    $success = session('status');
    $errors = $errors->all();
    $positionClasses = $placement === 'guest'
        ? 'top-16 sm:top-20'
        : 'top-24 sm:top-20';
@endphp

@if ($success || $errors !== [])
    <div
        {{ $attributes->merge(['class' => "pointer-events-none fixed inset-x-4 z-50 flex flex-col gap-3 sm:inset-x-auto sm:right-6 sm:w-[22rem] {$positionClasses}"]) }}
        data-toast-stack
        aria-live="polite"
    >
        @if ($success)
            <div
                data-toast
                data-toast-tone="success"
                data-toast-duration="5000"
                role="status"
                aria-live="polite"
                class="pointer-events-auto flex animate-rise items-start gap-3 rounded-2xl border border-emerald-400/35 bg-white/85 px-4 py-3 text-sm text-emerald-900 shadow-xl shadow-emerald-950/10 backdrop-blur-xl dark:border-emerald-400/25 dark:bg-slate-950/85 dark:text-emerald-100"
            >
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                </span>

                <span class="min-w-0 flex-1 font-medium">{{ $success }}</span>

                <button
                    type="button"
                    data-toast-dismiss
                    aria-label="Dismiss notification"
                    class="ml-1 shrink-0 rounded-lg p-1 text-emerald-700/70 transition hover:bg-emerald-500/10 hover:text-emerald-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-emerald-200/70 dark:hover:bg-emerald-400/10 dark:hover:text-emerald-100"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if ($errors !== [])
            <div
                data-toast
                data-toast-tone="error"
                data-toast-duration="8000"
                role="alert"
                aria-live="assertive"
                class="pointer-events-auto flex animate-rise items-start gap-3 rounded-2xl border border-rose-400/35 bg-white/85 px-4 py-3 text-sm text-rose-900 shadow-xl shadow-rose-950/10 backdrop-blur-xl dark:border-rose-400/25 dark:bg-slate-950/85 dark:text-rose-100"
            >
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-500/15 text-rose-700 dark:bg-rose-400/15 dark:text-rose-200">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 8v5M12 17h.01" />
                    </svg>
                </span>

                <ul class="min-w-0 flex-1 list-disc space-y-1 pl-4 font-medium">
                    @foreach ($errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                <button
                    type="button"
                    data-toast-dismiss
                    aria-label="Dismiss notification"
                    class="ml-1 shrink-0 rounded-lg p-1 text-rose-700/70 transition hover:bg-rose-500/10 hover:text-rose-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:text-rose-200/70 dark:hover:bg-rose-400/10 dark:hover:text-rose-100"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
    </div>
@endif
