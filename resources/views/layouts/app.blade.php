<!DOCTYPE html>
<html lang="ms" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a0715">
    <title>{{ $title ?? 'Dyno Ads' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-white antialiased">
    <div class="mx-auto flex min-h-full w-full max-w-md flex-col">
        <header class="flex items-center justify-between px-5 pb-2 pt-5">
            <a href="{{ route('ad-sets.create') }}" wire:navigate class="flex items-center gap-2.5">
                <img src="{{ asset('img/logo.png') }}" alt="" class="h-10 w-10 shrink-0">
                <span class="text-lg font-extrabold tracking-tight">
                    <span class="bg-dyno-gradient bg-clip-text text-transparent">DYNO</span><span class="text-white/85">ADS</span>
                </span>
            </a>
            <span class="text-[10px] font-semibold uppercase tracking-widest text-white/30">DynoPro</span>
        </header>

        <main class="flex-1 px-5 pb-24 pt-3">
            {{ $slot }}
        </main>

        <footer class="px-6 pb-8 pt-2 text-center text-[11px] leading-relaxed text-white/30">
            App bantu optimize iklan. Ia tak jamin lead.<br>
            Tiada duit dibelanjakan sebelum anda tekan RUN.
        </footer>
    </div>
    @livewireScripts
</body>
</html>
