<x-layouts.guest title="Create account">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Create your account</h1>
        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
            We only need your name and email. We will send a one-time code to verify it.
        </p>
    </div>

    <x-ui.alerts />

    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="label">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Your full name"
                class="field mt-2"
            >
        </div>

        <div>
            <label for="email" class="label">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                placeholder="you@example.com"
                class="field mt-2"
            >
        </div>

        <x-ui.button type="submit" size="lg" class="w-full">
            Create account
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
        Already registered?
        <a href="{{ route('login') }}" class="link">Sign in</a>
    </p>
</x-layouts.guest>
