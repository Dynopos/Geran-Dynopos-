<!DOCTYPE html>
<html lang="ms" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dyno Ads' }}</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @livewireStyles
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto flex min-h-full w-full max-w-md flex-col">
        <header class="flex items-center justify-between px-5 py-4">
            <a href="{{ route('ad-sets.create') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold tracking-tight">
                <img src="{{ asset('img/bob-dyno.jpg') }}" alt="Bob Dyno" class="h-9 w-9 rounded-full object-cover">
                Dyno<span class="-ml-1 text-orange-600">Ads</span>
            </a>
            <span class="text-xs text-slate-500">DYNOPRO</span>
        </header>

        <main class="flex-1 px-5 pb-24">
            {{ $slot }}
        </main>

        <footer class="px-5 pb-6 pt-2 text-center text-[11px] leading-relaxed text-slate-400">
            App bantu optimize iklan. Ia tak jamin lead.<br>
            Tiada duit dibelanjakan sebelum anda tekan RUN.
        </footer>
    </div>
    @livewireScripts
</body>
</html>
