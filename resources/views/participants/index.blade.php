<x-layouts.app title="Participants">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-violet-700 dark:text-violet-300">Guest list</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Participants</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                The gathering roster imported from spreadsheets and participant sign-ups.
            </p>
        </div>

        <a href="{{ route('home') }}" class="link text-sm">Back to home</a>
    </div>

    <x-ui.alerts />

    @if (! $hasAssignedBatch)
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-400/35 bg-amber-400/15 px-4 py-3 text-sm font-medium text-amber-900 backdrop-blur dark:text-amber-100">
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 9v4M12 17h.01" />
                <path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
            </svg>
            <span>No batch is assigned to your account. Contact the Super Admin.</span>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-ui.stat-card label="Participants" :value="$stats['active']" tone="violet" hint="On the guest list" />
        <x-ui.stat-card label="Relations attending" :value="$stats['relations']" tone="amber" hint="Guests joining in" />
    </div>

    @if ($canImport)
        <x-ui.glass-card title="Spreadsheet import" class="mb-6">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="max-w-md">
                    @if ($latestRun)
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Latest import:
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $latestRun->status->label() }}</span>
                            &middot; {{ $latestRun->created_at->diffForHumans() }}
                            @if ($latestRun->requestedBy)
                                by {{ $latestRun->requestedBy->name }}
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-slate-600 dark:text-slate-400">No spreadsheet has been imported yet.</p>
                    @endif

                    <p class="mt-3 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Upload a CSV or Excel file (up to 5 MB). Batch values must match an Excel mapper name in Batches.
                    </p>

                    @if ($latestRun?->isActive())
                        <p class="mt-3 text-sm font-medium text-violet-700 dark:text-violet-300">
                            The import is running. This page refreshes automatically.
                        </p>

                        <script>
                            window.setTimeout(() => window.location.reload(), 5000);
                        </script>
                    @endif

                    @if ($latestRun?->isCompleted())
                        <p class="mt-3 text-sm text-slate-700 dark:text-slate-300">{{ $latestRun->summary() }}</p>

                        @if ($latestRun->skipped_rows)
                            <details class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                                <summary class="cursor-pointer font-semibold text-slate-700 dark:text-slate-300">Skipped rows</summary>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($latestRun->skipped_rows as $skipped)
                                        <li>Row {{ $skipped['row'] }}: {{ $skipped['reason'] }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    @endif

                    @if ($latestRun?->hasFailed())
                        <p class="mt-3 rounded-xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm font-medium text-rose-800 dark:text-rose-200">
                            {{ $latestRun->failure_reason ?? 'The import failed.' }}
                        </p>
                    @endif
                </div>

                <div class="w-full max-w-sm">
                    <form method="POST" action="{{ route('participants.import') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <label for="file" class="sr-only">Spreadsheet file</label>
                        <input
                            id="file"
                            name="file"
                            type="file"
                            accept=".xlsx,.csv"
                            required
                            class="field"
                        >
                        <x-ui.button type="submit" class="w-full" :disabled="$latestRun?->isActive()">
                            {{ $latestRun?->isActive() ? 'Import in progress…' : 'Import spreadsheet' }}
                        </x-ui.button>
                    </form>
                </div>
            </div>

            @if ($runs->isNotEmpty())
                <div class="mt-6 border-t border-slate-300/40 pt-4 dark:border-white/10">
                    <h3 class="label">Recent runs</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                        @foreach ($runs as $run)
                            <li>
                                {{ $run->created_at->diffForHumans() }}
                                &middot; {{ $run->status->label() }}
                                @if ($run->source_name)
                                    &middot; {{ $run->source_name }}
                                @endif
                                @if ($run->isCompleted())
                                    &middot; {{ $run->summary() }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-ui.glass-card>
    @endif

    <section class="glass overflow-hidden">
        <form
            id="filters-form"
            method="GET"
            action="{{ route('participants.index') }}"
            data-auto-filter
            class="flex flex-wrap items-end gap-3 border-b border-slate-300/40 p-4 dark:border-white/10 sm:p-5"
        >
            <div class="min-w-48 flex-1">
                <label for="search" class="label">Search</label>
                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Name, phone, NIC or email"
                    class="field mt-1.5"
                >
            </div>

            @if ($canImport)
                <div>
                    <label for="batch" class="label">Batch</label>
                    <select id="batch" name="batch_id" class="field mt-1.5">
                        <option value="">All batches</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" @selected($filters['batchId'] === $batch->id)>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="attendance" class="label">Attendance</label>
                <select id="attendance" name="attendance" class="field mt-1.5">
                    <option value="">All attendance</option>
                    <option value="attending" @selected($filters['attendance'] === true)>Attending</option>
                    <option value="not_attending" @selected($filters['attendance'] === false)>Not attending</option>
                </select>
            </div>

            <a href="{{ route('participants.index') }}" class="link pb-2.5 text-sm">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                <thead class="table-head">
                    <tr>
                        <th scope="col" class="px-4 py-3">Participant</th>
                        <th scope="col" class="hidden px-4 py-3 sm:table-cell">Batch</th>
                        <th scope="col" class="hidden px-4 py-3 md:table-cell">Phone</th>
                        <th scope="col" class="px-4 py-3">Relations</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                    @forelse ($participants as $participant)
                        <tr
                            class="table-row cursor-pointer"
                            onclick="if (! event.target.closest('a, button, summary, input, textarea, select, form, details')) { window.location = '{{ route('participants.show', $participant) }}'; }"
                        >
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-start gap-3">
                                    <x-ui.avatar :name="$participant->name" size="sm" class="mt-0.5" />

                                    <div class="min-w-0">
                                        <p class="font-display text-[0.95rem] font-semibold text-slate-900 dark:text-white">
                                            <a href="{{ route('participants.show', $participant) }}" class="transition hover:text-violet-700 dark:hover:text-violet-300">
                                                {{ $participant->name }}
                                            </a>
                                        </p>

                                        @if ($participant->call_name)
                                            <p class="text-xs text-slate-500 dark:text-slate-400">&ldquo;{{ $participant->call_name }}&rdquo;</p>
                                        @endif

                                        @if ($participant->email)
                                            <p class="max-w-[14rem] truncate text-xs text-slate-500 dark:text-slate-400">{{ $participant->email }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="hidden px-4 py-3 align-top sm:table-cell">
                                @if ($participant->batch)
                                    <x-ui.badge tone="violet">{{ $participant->batch->name }}</x-ui.badge>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">&mdash;</span>
                                @endif
                            </td>

                            <td class="hidden px-4 py-3 align-top text-slate-600 dark:text-slate-300 md:table-cell">{{ $participant->phone ?? '—' }}</td>

                            <td class="px-4 py-3 align-top text-slate-600 dark:text-slate-300">
                                @if ($participant->relatedParticipants->isNotEmpty())
                                    {{ $participant->relatedParticipants->map(fn ($relation): string => $relation->name.' ('.$relation->relation?->label().')')->implode(', ') }}
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">&mdash;</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-wrap gap-1">
                                    @if ($participant->attending)
                                        <x-ui.badge tone="emerald">Attending</x-ui.badge>
                                    @else
                                        <x-ui.badge tone="rose">Not attending</x-ui.badge>
                                    @endif

                                    @if ($participant->import_key === null)
                                        <x-ui.badge tone="sky">Self-registered</x-ui.badge>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top text-right">
                                <a href="{{ route('participants.show', $participant) }}" class="link inline-block whitespace-nowrap text-xs">
                                    View details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-14 text-center">
                                <p class="font-display text-base font-semibold text-slate-700 dark:text-slate-200">No participants found</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No participants match the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($participants->hasPages())
            <div class="border-t border-slate-300/40 p-4 dark:border-white/10">
                {{ $participants->links() }}
            </div>
        @endif
    </section>
</x-layouts.app>
