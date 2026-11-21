<x-layouts.guest title="Sign in">
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight">Sign in</h1>
        <p class="mt-1 text-sm text-slate-500">
            Enter your email and we will send you a one-time code. No password needed.
        </p>
    </div>

    <x-ui.alerts />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-200"
        >
            Email me a sign-in code
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        New here?
        <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Create an account</a>
    </p>
</x-layouts.guest>
