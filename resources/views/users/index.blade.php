<x-layouts.app title="Users">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-violet-700 dark:text-violet-300">Access</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">Users</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Manage treasurer access. You cannot change your own role.
            </p>
        </div>

        <a href="{{ route('home') }}" class="link text-sm">Back to home</a>
    </div>


    <section class="glass overflow-hidden">
        <form
            id="filters-form"
            method="GET"
            action="{{ route('users.index') }}"
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
                    placeholder="Name or email"
                    class="field mt-1.5"
                >
            </div>

            <div>
                <label for="role" class="label">Role</label>
                <select id="role" name="role" class="field mt-1.5">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($filters['role']?->value === $role->value)>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="batch" class="label">Batch</label>
                <select id="batch" name="batch_id" class="field mt-1.5">
                    <option value="">All batches</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch->id }}" @selected($filters['batchId'] === $batch->id)>{{ $batch->name }}</option>
                    @endforeach
                </select>
            </div>

            <a href="{{ route('users.index') }}" class="link pb-2.5 text-sm">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                <thead class="table-head">
                    <tr>
                        <th scope="col" class="px-4 py-3">User</th>
                        <th scope="col" class="px-4 py-3">Role</th>
                        <th scope="col" class="hidden px-4 py-3 sm:table-cell">Batch</th>
                        <th scope="col" class="hidden px-4 py-3 md:table-cell">Joined</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Role actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                    @forelse ($users as $user)
                        <tr class="table-row">
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-start gap-3">
                                    <x-ui.avatar :name="$user->name" size="sm" class="mt-0.5" />

                                    <div>
                                        <p class="font-display text-[0.95rem] font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <x-ui.role-badge :role="$user->role" :label="$user->displayRoleLabel()" />
                            </td>

                            <td class="hidden px-4 py-3 align-top sm:table-cell">
                                @if ($user->batch)
                                    <x-ui.badge tone="violet">{{ $user->batch->name }}</x-ui.badge>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">&mdash;</span>
                                @endif
                            </td>

                            <td class="hidden px-4 py-3 align-top text-slate-600 dark:text-slate-300 md:table-cell">
                                {{ $user->created_at->format('j M Y') }}
                            </td>

                            <td class="px-4 py-3 align-top text-right">
                                @if (auth()->id() === $user->id)
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400"
                                        title="You cannot change your own role."
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect width="18" height="11" x="3" y="11" rx="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
                                        Protected
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('users.role.update', $user) }}" class="inline-flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')

                                        <label for="role-{{ $user->id }}" class="sr-only">Role for {{ $user->name }}</label>
                                        <select
                                            id="role-{{ $user->id }}"
                                            name="role"
                                            onchange="this.form.submit()"
                                            class="field w-auto py-1.5 text-xs"
                                        >
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-14 text-center">
                                <p class="font-display text-base font-semibold text-slate-700 dark:text-slate-200">No users found</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No users match the current search.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-300/40 p-4 dark:border-white/10">
                {{ $users->links() }}
            </div>
        @endif
    </section>
</x-layouts.app>
