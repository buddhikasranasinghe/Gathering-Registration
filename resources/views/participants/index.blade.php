<x-layouts.app title="Participants">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Participants</h1>
            <p class="mt-1 text-sm text-slate-500">
                The gathering roster imported from spreadsheets and participant sign-ups.
            </p>
        </div>

        <a href="{{ route('home') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            Back to home
        </a>
    </div>

    <x-ui.alerts />

    @if (! $hasAssignedBatch)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            No batch is assigned to your account. Contact the Super Admin.
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Participants</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ $stats['active'] }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Spouses attending</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ $stats['spouses'] }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Archived</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ $stats['archived'] }}</p>
        </div>
    </div>

    @if ($canImport)
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold tracking-tight">Spreadsheet import</h2>

                @if ($latestRun)
                    <p class="mt-1 text-sm text-slate-500">
                        Latest import:
                        <span class="font-medium text-slate-700">{{ $latestRun->status->label() }}</span>
                        · {{ $latestRun->created_at->diffForHumans() }}
                        @if ($latestRun->requestedBy)
                            by {{ $latestRun->requestedBy->name }}
                        @endif
                    </p>
                @else
                    <p class="mt-1 text-sm text-slate-500">No spreadsheet has been imported yet.</p>
                @endif
            </div>

            @if ($canImport)
                <div class="max-w-sm text-right">
                    <form method="POST" action="{{ route('participants.import') }}" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <label for="file" class="sr-only">Spreadsheet file</label>
                        <input
                            id="file"
                            name="file"
                            type="file"
                            accept=".xlsx,.csv"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        >
                        <button
                            type="submit"
                            @disabled($latestRun?->isActive())
                            class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            {{ $latestRun?->isActive() ? 'Import in progress…' : 'Import spreadsheet' }}
                        </button>
                    </form>

                    <p class="mt-2 text-xs text-slate-500">
                        Upload a CSV or Excel file (up to 5 MB). Batch values must match an Excel mapper name in Batches.
                    </p>
                </div>
            @endif
        </div>

        @if ($latestRun?->isActive())
            <p class="mt-4 text-sm text-slate-500">
                The import is running. This page refreshes automatically.
            </p>

            <script>
                window.setTimeout(() => window.location.reload(), 5000);
            </script>
        @endif

        @if ($latestRun?->isCompleted())
            <p class="mt-4 text-sm text-slate-700">{{ $latestRun->summary() }}</p>

            @if ($latestRun->skipped_rows)
                <details class="mt-3 text-sm text-slate-600">
                    <summary class="cursor-pointer font-medium text-slate-700">Skipped rows</summary>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($latestRun->skipped_rows as $skipped)
                            <li>Row {{ $skipped['row'] }}: {{ $skipped['reason'] }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        @endif

        @if ($latestRun?->hasFailed())
            <p class="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-inset ring-rose-200">
                {{ $latestRun->failure_reason ?? 'The import failed.' }}
            </p>
        @endif

        @if ($runs->isNotEmpty())
            <div class="mt-6 border-t border-slate-100 pt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recent runs</h3>
                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                    @foreach ($runs as $run)
                        <li>
                            {{ $run->created_at->diffForHumans() }}
                            · {{ $run->status->label() }}
                            @if ($run->source_name)
                                · {{ $run->source_name }}
                            @endif
                            @if ($run->isCompleted())
                                · {{ $run->summary() }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form
            id="filters-form"
            method="GET"
            action="{{ route('participants.index') }}"
            class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4"
        >
            <div class="min-w-48 flex-1">
                <label for="search" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Name, phone, NIC or email"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                >
            </div>

            @if ($canImport)
            <div>
                <label for="batch" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Batch</label>
                <select
                    id="batch"
                    name="batch_id"
                    class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                >
                    <option value="">All batches</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch->id }}" @selected($filters['batchId'] === $batch->id)>{{ $batch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <label class="flex items-center gap-2 pb-2 text-sm text-slate-600">
                <input type="checkbox" name="archived" value="1" @checked($filters['archived'])>
                Archived only
            </label>

            <a href="{{ route('participants.index') }}" class="pb-2 text-sm font-medium text-slate-500 hover:text-slate-700">
                Reset
            </a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Participant</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Batch</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Phone</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Spouse</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                        <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($participants as $participant)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-slate-900">{{ $participant->name }}</p>

                                @if ($participant->call_name)
                                    <p class="text-xs text-slate-500">&ldquo;{{ $participant->call_name }}&rdquo;</p>
                                @endif

                                @if ($participant->email)
                                    <p class="text-xs text-slate-500">{{ $participant->email }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top text-slate-600">{{ $participant->batch?->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $participant->phone ?? '—' }}</td>

                            <td class="px-4 py-3 align-top text-slate-600">
                                @if ($participant->spouse_attending === null)
                                    —
                                @elseif ($participant->spouse_attending)
                                    Yes{{ $participant->spouse_name ? ' · '.$participant->spouse_name : '' }}
                                @else
                                    No
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-wrap gap-1">
                                    @if ($participant->import_key === null)
                                        <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">
                                            Self-registered
                                        </span>
                                    @endif

                                    @if ($participant->isArchived())
                                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">
                                            Archived
                                        </span>
                                    @endif
                                </div>

                                @if ($participant->isArchived())
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $participant->archive_note }}
                                        @if ($participant->archivedBy)
                                            · {{ $participant->archivedBy->name }}
                                        @endif
                                        @if ($participant->archived_at)
                                            · {{ $participant->archived_at->diffForHumans() }}
                                        @endif
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top text-right">
                                @if ($canArchive && ! $participant->isArchived())
                                    <details class="text-left">
                                        <summary class="cursor-pointer text-xs font-medium text-rose-600 hover:text-rose-500">
                                            Archive
                                        </summary>

                                        <form
                                            method="POST"
                                            action="{{ route('participants.archive', $participant) }}"
                                            class="mt-2 w-56 space-y-2"
                                        >
                                            @csrf

                                            <label for="note-{{ $participant->id }}" class="sr-only">Archive note</label>
                                            <textarea
                                                id="note-{{ $participant->id }}"
                                                name="note"
                                                rows="2"
                                                required
                                                minlength="3"
                                                maxlength="1000"
                                                placeholder="Why is this participant archived?"
                                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
                                            ></textarea>

                                            <button
                                                type="submit"
                                                class="w-full rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-500 focus:outline-none focus:ring-4 focus:ring-rose-200"
                                            >
                                                Archive participant
                                            </button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                No participants match the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($participants->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $participants->links() }}
            </div>
        @endif
    </section>
    <script>
        (function () {
            const form = document.getElementById('filters-form');
            if (!form) return;

            let timer = null;
            const submit = () => form.submit();

            form.querySelectorAll('select, input[type="checkbox"]').forEach((el) => {
                el.addEventListener('change', submit);
            });

            const search = form.querySelector('input[name="search"]');
            if (search) {
                search.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(submit, 400);
                });
            }
        })();
    </script>
</x-layouts.app>
