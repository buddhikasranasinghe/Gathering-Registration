<x-layouts.app title="Add batch">
    <div class="mb-6">
        <a href="{{ route('batches.index') }}" class="link text-sm">&larr; Back to batches</a>
        <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Add batch</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Create a display name and the value used by Excel imports.</p>
    </div>


    <x-ui.glass-card padding="p-6 sm:p-8" class="max-w-2xl">
        <form method="POST" action="{{ route('batches.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="label">Batch name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" placeholder="e.g. 40 (2016 - 2017)" class="field mt-1.5">
                @error('name')
                    <p class="mt-1.5 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="excel_mapper_name" class="label">Excel mapper name</label>
                <input id="excel_mapper_name" name="excel_mapper_name" type="text" value="{{ old('excel_mapper_name') }}" required maxlength="255" placeholder="e.g. 40" class="field mt-1.5">
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">This is converted to lowercase and must match the batch value in imported spreadsheets.</p>
                @error('excel_mapper_name')
                    <p class="mt-1.5 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <x-ui.button type="submit">Create batch</x-ui.button>
                <a href="{{ route('batches.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </x-ui.glass-card>
</x-layouts.app>
