<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">
                {{ config('app.name') }}
            </a>

            <div class="flex items-center gap-4 text-sm">
                @if (auth()->user()->isAdministrator())
                    <a href="{{ route('participants.index') }}" class="font-medium text-slate-600 transition hover:text-slate-900">
                        Participants
                    </a>
                @endif

                <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                <x-ui.role-badge :role="auth()->user()->role" />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg px-3 py-1.5 font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        {{ $slot }}
    </main>
</body>
</html>
