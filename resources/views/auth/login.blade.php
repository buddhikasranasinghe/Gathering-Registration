<x-layouts.guest title="Sign in">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Welcome back</h1>
        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
            Enter your email and we will send you a one-time code. No password needed.
        </p>
    </div>

    <x-ui.alerts />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="you@example.com"
                class="field mt-2"
            >
        </div>

        <x-ui.button type="submit" size="lg" class="w-full">
            Email me a sign-in code
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
        New here?
        <a href="{{ route('register') }}" class="link">Create an account</a>
    </p>
</x-layouts.guest>
