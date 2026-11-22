<x-layouts.app title="Users">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage administrator access. The Super Admin role is protected.
            </p>
        </div>

        <a href="{{ route('home') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            Back to home
        </a>
    </div>

    <x-ui.alerts />

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-48 flex-1">
                <label for="search" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Name or email"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                >
            </div>

            <button
                type="submit"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-200"
            >
                Search
            </button>

            <a href="{{ route('users.index') }}" class="pb-2 text-sm font-medium text-slate-500 hover:text-slate-700">
                Reset
            </a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">User</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Role</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Joined</th>
                        <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Role actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-slate-900">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <x-ui.role-badge :role="$user->role" />
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">
                                {{ $user->created_at->format('j M Y') }}
                            </td>
                            <td class="px-4 py-3 align-top text-right">
                                @if ($user->isSuperAdmin())
                                    <span class="text-xs font-medium text-slate-500">Protected</span>
                                @else
                                    <form method="POST" action="{{ route('users.role.update', $user) }}" class="inline-flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')

                                        <label for="role-{{ $user->id }}" class="sr-only">Role for {{ $user->name }}</label>
                                        <select
                                            id="role-{{ $user->id }}"
                                            name="role"
                                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                                        >
                                            <option value="user" @selected($user->role === \App\Identity\Entities\UserRole::User)>User</option>
                                            <option value="admin" @selected($user->role === \App\Identity\Entities\UserRole::Admin)>Admin</option>
                                        </select>

                                        <button
                                            type="submit"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                                        >
                                            Update role
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                No users match the current search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $users->links() }}
            </div>
        @endif
    </section>
</x-layouts.app>
