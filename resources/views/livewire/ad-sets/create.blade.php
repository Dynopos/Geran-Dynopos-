<div>
    <h1 class="text-2xl font-bold leading-tight">Buat iklan baru</h1>
    <p class="mt-1 text-sm text-slate-500">
        Satu gambar = satu iklan. Kita bandingkan gambar mana yang paling murah kosnya.
    </p>

    <form wire:submit="save" class="mt-6 space-y-6">

        {{-- 1. Gambar --}}
        <div>
            <label class="block text-sm font-semibold">Gambar iklan <span class="text-slate-400">(1–4)</span></label>
            <input type="file" wire:model="images" multiple accept="image/*"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white p-3 text-sm">
            <p class="mt-1 text-xs text-slate-500">Gambar akan dipotong jadi persegi 1080×1080.</p>
            @error('images') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('images.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

            <div wire:loading wire:target="images" class="mt-2 text-xs text-slate-500">Memuat naik gambar…</div>

            @if ($images)
                <div class="mt-3 grid grid-cols-4 gap-2">
                    @foreach ($images as $image)
                        @if (method_exists($image, 'temporaryUrl'))
                            <img src="{{ $image->temporaryUrl() }}" class="aspect-square w-full rounded-lg object-cover">
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 2. Masalah --}}
        <div>
            <label class="block text-sm font-semibold">Masalah pelanggan anda</label>
            <textarea wire:model="problem" rows="2" placeholder="Contoh: kira duit lambat waktu peak hour"
                      class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm"></textarea>
            @error('problem') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- 3. Tawaran --}}
        <div>
            <label class="block text-sm font-semibold">Apa yang anda tawarkan</label>
            <textarea wire:model="offer" rows="2" placeholder="Contoh: sistem POS fullset, pasang di kedai"
                      class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm"></textarea>
            @error('offer') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- 4. Telefon --}}
        <div>
            <label class="block text-sm font-semibold">Nombor WhatsApp</label>
            <input type="tel" wire:model="phone" inputmode="numeric" placeholder="60187922844"
                   class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm">
            <p class="mt-1 text-xs text-slate-500">Format 60XXXXXXXXX, tiada tanda +.</p>
            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- 5. Kawasan --}}
        <div>
            <label class="block text-sm font-semibold">Kawasan</label>
            <select wire:model="regionKey" class="mt-2 w-full rounded-xl border border-slate-300 bg-white p-3 text-sm">
                <option value="">Seluruh Malaysia</option>
                @foreach ($regions as $region)
                    <option value="{{ $region['key'] }}">{{ $region['name'] }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">
                Seluruh Malaysia tidak termasuk Sabah, Sarawak dan Labuan.
                @if (! $regions)
                    Senarai negeri perlukan sambungan Meta.
                @endif
            </p>
        </div>

        {{-- 6. Bajet --}}
        <div>
            <label class="block text-sm font-semibold">Bajet sehari, setiap iklan</label>
            <div class="mt-2 flex gap-2">
                @foreach ([20, 30, 37, 50] as $preset)
                    <button type="button" wire:click="$set('budgetRm', {{ $preset }})"
                            class="flex-1 rounded-xl border px-2 py-3 text-sm font-semibold
                                   {{ $budgetRm === $preset ? 'border-orange-600 bg-orange-50 text-orange-700' : 'border-slate-300 bg-white' }}">
                        RM{{ $preset }}
                    </button>
                @endforeach
            </div>
            <input type="number" wire:model.live="budgetRm" min="10" max="200"
                   class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm">
            @error('budgetRm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

            @if (count($images))
                <p class="mt-2 rounded-lg bg-slate-100 p-3 text-xs text-slate-600">
                    {{ count($images) }} iklan × RM{{ $budgetRm }} =
                    <span class="font-bold">RM{{ count($images) * $budgetRm }} sehari</span> bila anda tekan RUN nanti.
                </p>
            @endif
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="save,images"
                class="w-full rounded-xl bg-orange-600 px-4 py-4 text-base font-bold text-white disabled:opacity-50">
            <span wire:loading.remove wire:target="save">Teruskan ke semakan</span>
            <span wire:loading wire:target="save">Sedang siapkan…</span>
        </button>

        <p class="text-center text-xs text-slate-500">
            Belum ada apa-apa dihantar ke Meta. Anda semak dulu.
        </p>
    </form>
</div>
