<x-layouts.app title="Edit batch">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Edit batch</h1>
        <p class="mt-1 text-sm text-slate-500">Update the display name or the Excel import mapper.</p>
    </div>

    <x-ui.alerts />

    <form method="POST" action="{{ route('batches.update', $batch) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PATCH')

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Batch name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $batch->name) }}" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('name')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="excel_mapper_name" class="block text-sm font-medium text-slate-700">Excel mapper name</label>
            <input id="excel_mapper_name" name="excel_mapper_name" type="text" value="{{ old('excel_mapper_name', $batch->excel_mapper_name) }}" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            <p class="mt-1 text-xs text-slate-500">This is converted to lowercase and must match the batch value in imported spreadsheets.</p>
            @error('excel_mapper_name')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                Save changes
            </button>
            <a href="{{ route('batches.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</a>
        </div>
    </form>
</x-layouts.app>
