<x-layouts.app title="Home">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Welcome, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    You are signed in with a verified email address.
                </p>
            </div>

            <x-ui.role-badge :role="auth()->user()->role" />
        </div>

        <dl class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</dt>
                <dd class="mt-1 break-all text-sm font-medium text-slate-800">{{ auth()->user()->email }}</dd>
            </div>

            <div class="rounded-xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Role</dt>
                <dd class="mt-1 text-sm font-medium text-slate-800">{{ auth()->user()->role->label() }}</dd>
            </div>
        </dl>
    </div>
</x-layouts.app>
