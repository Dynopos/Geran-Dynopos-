<div>
    <h1 class="text-2xl font-bold leading-tight">Semak dulu</h1>
    <p class="mt-1 text-sm text-slate-500">
        AI Nurin dah tulis caption. Ubah ikut suka anda — anda yang kenal pelanggan.
    </p>

    @if ($error)
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="mt-6 space-y-5">
        @foreach ($adSet->variants as $variant)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <img src="{{ Storage::disk('public')->url($variant->image_path) }}"
                     alt="Iklan {{ $variant->position }}" class="aspect-square w-full object-cover">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Iklan #{{ $variant->position }}
                        </span>
                        @if ($variant->meta_campaign_id)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">Sudah dibuat</span>
                        @endif
                    </div>
                    <textarea wire:model="captions.{{ $variant->id }}" rows="5"
                              class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm leading-relaxed"></textarea>
                    <p class="mt-2 text-xs text-slate-400">Butang iklan: Tekan WhatsApp untuk info lanjut</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-2xl bg-white p-4 text-sm">
        <div class="flex justify-between py-1"><span class="text-slate-500">Kawasan</span><span class="font-semibold">{{ $adSet->regionLabel() }}</span></div>
        <div class="flex justify-between py-1"><span class="text-slate-500">WhatsApp</span><span class="font-semibold">{{ $adSet->phone }}</span></div>
        <div class="flex justify-between py-1"><span class="text-slate-500">Bajet sehari</span><span class="font-semibold">RM{{ intdiv($adSet->daily_budget_sen, 100) }} × {{ $adSet->variants->count() }} iklan</span></div>
        <div class="mt-2 flex justify-between border-t border-slate-100 pt-2">
            <span class="text-slate-500">Jumlah sehari</span>
            <span class="font-bold text-orange-700">RM{{ intdiv($adSet->totalDailyBudgetSen(), 100) }}</span>
        </div>
    </div>

    <button type="button" wire:click="regenerate" wire:loading.attr="disabled" wire:target="regenerate"
            class="mt-4 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold">
        <span wire:loading.remove wire:target="regenerate">Minta caption lain</span>
        <span wire:loading wire:target="regenerate">AI Nurin sedang tulis…</span>
    </button>

    <button type="button" wire:click="approve" wire:loading.attr="disabled" wire:target="approve"
            class="mt-3 w-full rounded-xl bg-orange-600 px-4 py-4 text-base font-bold text-white disabled:opacity-50">
        <span wire:loading.remove wire:target="approve">Sahkan &amp; buat iklan (masih PAUSED)</span>
        <span wire:loading wire:target="approve">Sedang buat di Meta…</span>
    </button>

    <p class="mt-3 text-center text-xs text-slate-500">
        Iklan dibuat dalam keadaan PAUSED. Tiada duit dibelanjakan sampai anda tekan RUN.
    </p>
</div>
