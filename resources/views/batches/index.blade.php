<x-layouts.app title="Batches">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Batches</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage the names shown in the roster and the values matched during imports.
            </p>
        </div>

        <a
            href="{{ route('batches.create') }}"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-200"
        >
            Add batch
        </a>
    </div>

    <x-ui.alerts />

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Batch name</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Excel mapper name</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Participants</th>
                        <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($batches as $batch)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $batch->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $batch->excel_mapper_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $batch->participants_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('batches.edit', $batch) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                No batches have been created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
