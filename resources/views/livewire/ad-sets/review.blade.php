<div>
    <h1 class="text-[28px] font-extrabold leading-tight tracking-tight">Semak dulu</h1>
    <p class="mt-1.5 text-sm leading-relaxed text-white/50">
        AI Nurin dah tulis caption. Ubah ikut suka anda — anda yang kenal pelanggan.
    </p>

    @if ($error)
        <div class="mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 p-3.5 text-sm text-rose-200">{{ $error }}</div>
    @endif

    <div class="mt-6 space-y-5">
        @foreach ($adSet->variants as $variant)
            <div class="card overflow-hidden">
                <div class="relative">
                    <img src="{{ Storage::disk('public')->url($variant->image_path) }}"
                         alt="Iklan {{ $variant->position }}" class="aspect-square w-full object-cover">
                    <span class="absolute left-3 top-3 rounded-lg bg-black/65 px-2 py-1 text-[11px] font-bold backdrop-blur-sm">
                        Iklan #{{ $variant->position }}
                    </span>
                    @if ($variant->meta_campaign_id)
                        <span class="absolute right-3 top-3 rounded-lg bg-emerald-500/20 px-2 py-1 text-[11px] font-semibold text-emerald-300 backdrop-blur-sm">
                            Sudah dibuat
                        </span>
                    @endif
                </div>
                <div class="p-4">
                    <textarea wire:model="captions.{{ $variant->id }}" rows="5" class="field leading-relaxed"></textarea>
                    <p class="hint">Butang iklan: Tekan WhatsApp untuk info lanjut</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-6 divide-y divide-white/5 p-4 text-sm">
        <div class="flex items-start justify-between gap-4 pb-2.5">
            <span class="text-white/45">Kawasan</span>
            <span class="text-right font-semibold">{{ $adSet->regionLabel() }}</span>
        </div>
        <div class="flex justify-between py-2.5">
            <span class="text-white/45">WhatsApp</span>
            <span class="font-semibold">{{ $adSet->phone }}</span>
        </div>
        <div class="flex justify-between py-2.5">
            <span class="text-white/45">Bajet sehari</span>
            <span class="font-semibold">RM{{ intdiv($adSet->daily_budget_sen, 100) }} &times; {{ $adSet->variants->count() }} iklan</span>
        </div>
        <div class="flex items-center justify-between pt-2.5">
            <span class="text-white/45">Jumlah sehari</span>
            <span class="bg-dyno-gradient bg-clip-text text-lg font-extrabold text-transparent">
                RM{{ intdiv($adSet->totalDailyBudgetSen(), 100) }}
            </span>
        </div>
    </div>

    <button type="button" wire:click="regenerate" class="btn-ghost mt-4"
            wire:loading.attr="disabled" wire:target="regenerate">
        <span wire:loading.remove wire:target="regenerate">Minta caption lain</span>
        <span wire:loading wire:target="regenerate">AI Nurin sedang tulis…</span>
    </button>

    <button type="button" wire:click="approve" class="btn-primary mt-3"
            wire:loading.attr="disabled" wire:target="approve">
        <span wire:loading.remove wire:target="approve">Sahkan &amp; buat iklan</span>
        <span wire:loading wire:target="approve">Sedang buat di Meta…</span>
    </button>

    <p class="mt-3 text-center text-xs leading-relaxed text-white/40">
        Iklan dibuat dalam keadaan PAUSED.<br>Tiada duit dibelanjakan sampai anda tekan RUN.
    </p>
</div>
