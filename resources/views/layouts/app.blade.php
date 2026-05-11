<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|space-grotesk:500,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased app-shell-bg text-slate-100">
        <div class="min-h-screen">
            {{-- Impersonation banner --}}
            @if (session()->has('impersonate'))
                <div class="w-full bg-amber-400/95 text-slate-950 py-2 px-5 text-sm flex items-center justify-between z-50">
                    <span>⚡ You are impersonating a user.</span>
                    <form method="POST" action="{{ route('admin.impersonate.stop') }}" class="inline">
                        @csrf
                        <button class="font-semibold underline hover:no-underline" type="submit">Stop & return to admin</button>
                    </form>
                </div>
            @endif

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                    <div class="glass-card px-6 py-5">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-10">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
