<div>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-[28px] font-extrabold leading-tight tracking-tight">Prestasi</h1>
            <p class="mt-1 truncate text-sm text-white/50">{{ $adSet->offer }}</p>
        </div>
        <button type="button" wire:click="refreshMetrics" wire:loading.attr="disabled" wire:target="refreshMetrics"
                class="mt-1 shrink-0 rounded-lg border border-white/15 bg-white/[0.04] px-3 py-2 text-xs font-semibold">
            <span wire:loading.remove wire:target="refreshMetrics">Refresh</span>
            <span wire:loading wire:target="refreshMetrics">Menarik…</span>
        </button>
    </div>

    @if ($notice)
        <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3.5 text-sm text-emerald-200">{{ $notice }}</div>
    @endif

    <div class="mt-5 grid grid-cols-3 gap-2.5">
        <div class="stat">
            <div class="stat-label">Belanja</div>
            <div class="stat-value">RM{{ number_format($totalSpendSen / 100, 0) }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Lead</div>
            <div class="stat-value bg-dyno-gradient bg-clip-text text-transparent">{{ $totalLeads }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Kos/lead</div>
            <div class="stat-value">
                {{ $totalLeads > 0 ? 'RM'.number_format($totalSpendSen / $totalLeads / 100, 0) : '—' }}
            </div>
        </div>
    </div>

    @php $best = $variants->filter(fn ($v) => $v->leads() > 0)->sortBy(fn ($v) => $v->costPerLeadSen())->first(); @endphp

    <h2 class="mt-8 text-xs font-semibold uppercase tracking-widest text-white/35">Setiap iklan</h2>
    <div class="mt-3 space-y-2.5">
        @foreach ($variants as $variant)
            @php $cpl = $variant->costPerLeadSen(); @endphp
            <div class="card flex gap-3 p-3 {{ $best && $best->is($variant) ? 'ring-1 ring-dyno-purple/50' : '' }}">
                <img src="{{ Storage::disk('public')->url($variant->image_path) }}" alt=""
                     class="h-16 w-16 shrink-0 rounded-xl border border-white/10 object-cover">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold">Iklan #{{ $variant->position }}</span>
                        @if ($best && $best->is($variant))
                            <span class="rounded-md bg-dyno-gradient px-1.5 py-0.5 text-[10px] font-bold">TERBAIK</span>
                        @endif
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                        <div>
                            <div class="text-[10px] uppercase tracking-wide text-white/35">Belanja</div>
                            <div class="mt-0.5 font-semibold">RM{{ number_format($variant->spendSen() / 100, 0) }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-wide text-white/35">Lead</div>
                            <div class="mt-0.5 font-semibold">{{ $variant->leads() }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-wide text-white/35">Kos/lead</div>
                            <div class="mt-0.5 font-semibold">{{ $cpl !== null ? 'RM'.number_format($cpl / 100, 0) : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h2 class="mt-8 text-xs font-semibold uppercase tracking-widest text-white/35">Apa app dah buat</h2>
    <ol class="mt-3 space-y-2">
        @forelse ($actions as $action)
            @php
                $tone = match ($action->result) {
                    'ok' => 'bg-emerald-400',
                    'failed' => 'bg-rose-400',
                    default => 'bg-white/25',
                };
            @endphp
            <li class="card flex gap-3 p-3 text-xs">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $tone }}"></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold leading-relaxed">{{ $action->reason }}</p>
                    @if ($action->message)
                        <p class="mt-1 leading-relaxed text-white/45">{{ $action->message }}</p>
                    @endif
                    <p class="mt-1 text-white/30">{{ $action->created_at->timezone(config('app.timezone'))->format('d M, h:i A') }}</p>
                </div>
            </li>
        @empty
            <li class="rounded-xl border border-dashed border-white/15 p-5 text-center text-xs text-white/35">
                Belum ada tindakan direkod.
            </li>
        @endforelse
    </ol>

    <a href="{{ route('ad-sets.run', $adSet) }}" wire:navigate class="btn-ghost mt-6 block text-center">
        Kembali ke kawalan iklan
    </a>
</div>
