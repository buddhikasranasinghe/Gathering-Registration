<x-layouts.guest title="Verify your email">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Check your email</h1>
        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
            Enter the {{ config('otp.code_length') }}-digit code we sent to
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $email }}</span>.
            It expires in {{ config('otp.expires_after_minutes') }} minutes.
        </p>
    </div>

    <x-ui.alerts />

    <form method="POST" action="{{ route('verify.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="label">Verification code</label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="{{ config('otp.code_length') }}"
                value="{{ old('code') }}"
                required
                autofocus
                autocomplete="one-time-code"
                placeholder="••••••"
                class="field mt-2 py-3 text-center font-mono text-2xl tracking-[0.4em]"
            >
        </div>

        <x-ui.button type="submit" size="lg" class="w-full">
            Verify and continue
        </x-ui.button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-3 text-sm">
        <a href="{{ route('login') }}" class="text-slate-600 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">
            Use a different email
        </a>

        <form method="POST" action="{{ route('verify.resend') }}">
            @csrf
            <button
                type="submit"
                data-resend-button
                data-cooldown="{{ $resendAfter }}"
                class="link disabled:cursor-not-allowed disabled:text-slate-400 disabled:dark:text-slate-500"
            >
                Resend code
            </button>
        </form>
    </div>
</x-layouts.guest>

@push('scripts')
    <script>
        document.querySelectorAll('[data-resend-button]').forEach((button) => {
            let seconds = Number(button.dataset.cooldown || 0);

            const render = () => {
                if (seconds > 0) {
                    button.disabled = true;
                    button.textContent = `Resend code in ${seconds}s`;
                } else {
                    button.disabled = false;
                    button.textContent = 'Resend code';
                }
            };

            render();

            if (seconds > 0) {
                const timer = window.setInterval(() => {
                    seconds -= 1;
                    render();

                    if (seconds <= 0) {
                        window.clearInterval(timer);
                    }
                }, 1000);
            }
        });
    </script>
@endpush
