<div>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold leading-tight">Prestasi</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $adSet->offer }}</p>
        </div>
        <button type="button" wire:click="refreshMetrics" wire:loading.attr="disabled" wire:target="refreshMetrics"
                class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">
            <span wire:loading.remove wire:target="refreshMetrics">Refresh</span>
            <span wire:loading wire:target="refreshMetrics">Menarik…</span>
        </button>
    </div>

    @if ($notice)
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ $notice }}</div>
    @endif

    <div class="mt-5 grid grid-cols-3 gap-2">
        <div class="rounded-2xl bg-white p-3">
            <div class="text-[11px] uppercase tracking-wide text-slate-400">Belanja</div>
            <div class="mt-1 text-lg font-bold">RM{{ number_format($totalSpendSen / 100, 2) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-3">
            <div class="text-[11px] uppercase tracking-wide text-slate-400">Lead</div>
            <div class="mt-1 text-lg font-bold">{{ $totalLeads }}</div>
        </div>
        <div class="rounded-2xl bg-white p-3">
            <div class="text-[11px] uppercase tracking-wide text-slate-400">Kos/lead</div>
            <div class="mt-1 text-lg font-bold">
                {{ $totalLeads > 0 ? 'RM'.number_format($totalSpendSen / $totalLeads / 100, 2) : '—' }}
            </div>
        </div>
    </div>

    <h2 class="mt-7 text-sm font-semibold uppercase tracking-wide text-slate-400">Setiap iklan</h2>
    <div class="mt-3 space-y-3">
        @foreach ($variants as $variant)
            <div class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-3">
                <img src="{{ Storage::disk('public')->url($variant->image_path) }}"
                     alt="Iklan {{ $variant->position }}" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                <div class="min-w-0 flex-1 text-sm">
                    <div class="font-semibold">Iklan #{{ $variant->position }}</div>
                    <div class="mt-1 grid grid-cols-3 gap-2 text-xs text-slate-600">
                        <span>RM{{ number_format($variant->spendSen() / 100, 2) }}</span>
                        <span>{{ $variant->leads() }} lead</span>
                        <span>
                            {{ $variant->costPerLeadSen() !== null ? 'RM'.number_format($variant->costPerLeadSen() / 100, 2) : '—' }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h2 class="mt-7 text-sm font-semibold uppercase tracking-wide text-slate-400">Apa app dah buat</h2>
    <ol class="mt-3 space-y-2">
        @forelse ($actions as $action)
            <li class="rounded-xl border border-slate-200 bg-white p-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-semibold">{{ $action->reason }}</span>
                    @php
                        $tone = match ($action->result) {
                            'ok' => 'text-emerald-600',
                            'failed' => 'text-red-600',
                            default => 'text-slate-400',
                        };
                    @endphp
                    <span class="{{ $tone }}">{{ strtoupper($action->result) }}</span>
                </div>
                @if ($action->message)
                    <p class="mt-1 text-slate-500">{{ $action->message }}</p>
                @endif
                <p class="mt-1 text-slate-400">{{ $action->created_at->timezone(config('app.timezone'))->format('d M Y, h:i A') }}</p>
            </li>
        @empty
            <li class="rounded-xl border border-dashed border-slate-300 p-4 text-center text-xs text-slate-400">
                Belum ada tindakan direkod.
            </li>
        @endforelse
    </ol>

    <a href="{{ route('ad-sets.run', $adSet) }}" wire:navigate
       class="mt-6 block rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-sm font-semibold">
        Kembali ke kawalan iklan
    </a>
</div>
