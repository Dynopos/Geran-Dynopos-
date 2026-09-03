<div>
    <h1 class="text-2xl font-bold leading-tight">Lancarkan</h1>
    <p class="mt-1 text-sm text-slate-500">
        Semua iklan sedang PAUSED. Tekan RUN bila anda dah sedia.
    </p>

    @if ($error)
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $error }}</div>
    @endif
    @if ($notice)
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ $notice }}</div>
    @endif

    <div class="mt-5 rounded-2xl bg-white p-4 text-sm">
        <div class="flex justify-between py-1">
            <span class="text-slate-500">Belanja sehari kalau semua hidup</span>
            <span class="font-bold text-orange-700">RM{{ intdiv($adSet->totalDailyBudgetSen(), 100) }}</span>
        </div>
        <div class="flex justify-between py-1">
            <span class="text-slate-500">Kawasan</span>
            <span class="font-semibold">{{ $adSet->regionLabel() }}</span>
        </div>
    </div>

    <button type="button" wire:click="runAll" wire:loading.attr="disabled" wire:target="runAll"
            wire:confirm="Selepas ini duit mula dibelanjakan. Teruskan?"
            class="mt-4 w-full rounded-xl bg-emerald-600 px-4 py-5 text-lg font-bold text-white disabled:opacity-50">
        <span wire:loading.remove wire:target="runAll">RUN SEMUA</span>
        <span wire:loading wire:target="runAll">Sedang hidupkan…</span>
    </button>

    <div class="mt-6 space-y-4">
        @foreach ($adSet->variants as $variant)
            <div class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-3">
                <img src="{{ Storage::disk('public')->url($variant->image_path) }}"
                     alt="Iklan {{ $variant->position }}" class="h-20 w-20 shrink-0 rounded-lg object-cover">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold">Iklan #{{ $variant->position }}</span>
                        @php
                            $badge = match ($variant->status) {
                                'active' => ['bg-emerald-100 text-emerald-700', 'HIDUP'],
                                'failed' => ['bg-red-100 text-red-700', 'GAGAL'],
                                default => ['bg-slate-100 text-slate-600', 'PAUSED'],
                            };
                        @endphp
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $badge[0] }}">{{ $badge[1] }}</span>
                    </div>
                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $variant->caption }}</p>

                    @if ($variant->last_error)
                        <p class="mt-1 text-xs text-red-600">{{ $variant->last_error }}</p>
                    @endif

                    @if ($variant->meta_ad_id)
                        <div class="mt-2">
                            @if ($variant->isLive())
                                <button type="button" wire:click="pauseVariant({{ $variant->id }})"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold">Pause</button>
                            @else
                                <button type="button" wire:click="runVariant({{ $variant->id }})"
                                        class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Hidupkan</button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <a href="{{ route('ad-sets.dashboard', $adSet) }}" wire:navigate
       class="mt-6 block rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-sm font-semibold">
        Lihat prestasi
    </a>
</div>
