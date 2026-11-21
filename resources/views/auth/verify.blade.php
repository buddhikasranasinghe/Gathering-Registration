<x-layouts.guest title="Verify your email">
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight">Check your email</h1>
        <p class="mt-1 text-sm text-slate-500">
            Enter the {{ config('otp.code_length') }}-digit code we sent to
            <span class="font-medium text-slate-700">{{ $email }}</span>.
            It expires in {{ config('otp.expires_after_minutes') }} minutes.
        </p>
    </div>

    <x-ui.alerts />

    <form method="POST" action="{{ route('verify.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-slate-700">Verification code</label>
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
                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-center font-mono text-2xl tracking-[0.4em] shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-200"
        >
            Verify and continue
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
        <a href="{{ route('login') }}" class="text-slate-500 transition hover:text-slate-700">
            Use a different email
        </a>

        <form method="POST" action="{{ route('verify.resend') }}">
            @csrf
            <button
                type="submit"
                data-resend-button
                data-cooldown="{{ $resendAfter }}"
                class="font-medium text-indigo-600 transition hover:text-indigo-500 disabled:cursor-not-allowed disabled:text-slate-400"
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
