<x-layouts.app title="Batches">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-violet-700 dark:text-violet-300">Groups</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Batches</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Manage the names shown in the roster and the values matched during imports.
            </p>
        </div>

        <x-ui.button :href="route('batches.create')">Add batch</x-ui.button>
    </div>

    <x-ui.alerts />

    <section class="glass overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                <thead class="table-head">
                    <tr>
                        <th scope="col" class="px-4 py-3">Batch name</th>
                        <th scope="col" class="hidden px-4 py-3 sm:table-cell">Excel mapper name</th>
                        <th scope="col" class="px-4 py-3">Participants</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                    @forelse ($batches as $batch)
                        <tr class="table-row">
                            <td class="px-4 py-3">
                                <p class="font-display font-semibold text-slate-900 dark:text-white">{{ $batch->name }}</p>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                <span class="rounded-lg bg-slate-500/10 px-2 py-1 font-mono text-xs text-slate-700 dark:bg-white/5 dark:text-slate-300">{{ $batch->excel_mapper_name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge tone="violet">{{ $batch->participants_count }} {{ \Illuminate\Support\Str::plural('member', $batch->participants_count) }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('batches.edit', $batch) }}" class="link text-sm">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-14 text-center">
                                <p class="font-display text-base font-semibold text-slate-700 dark:text-slate-200">No batches yet</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No batches have been created yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
