<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-white to-indigo-50 px-4 py-10 font-sans text-slate-900 antialiased">
    <main class="w-full max-w-md">
        <div class="mb-8 text-center">
            <a href="{{ route('home') }}" class="text-2xl font-semibold tracking-tight text-slate-900">
                {{ config('app.name') }}
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            {{ $slot }}
        </div>

        <p class="mt-8 text-center text-xs text-slate-400">
            Passwordless sign-in &middot; One-time email code
        </p>
    </main>

    @stack('scripts')
</body>
</html>
