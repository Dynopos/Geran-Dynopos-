<div>
    <h1 class="text-[28px] font-extrabold leading-tight tracking-tight">Buat iklan baru</h1>
    <p class="mt-1.5 text-sm leading-relaxed text-white/50">
        Satu gambar = satu iklan. Kita bandingkan gambar mana yang paling murah kosnya.
    </p>

    <form wire:submit="save" class="mt-7 space-y-7">

        {{-- Gambar --}}
        <div>
            <div class="flex items-baseline justify-between">
                <span class="label">Gambar iklan</span>
                <span class="text-xs font-semibold text-white/40">{{ count($images) }} / {{ config('dynoads.creative.max_images') }}</span>
            </div>

            @if ($images)
                <div class="mt-3 grid grid-cols-4 gap-2">
                    @foreach ($images as $i => $image)
                        <div class="relative">
                            <img src="{{ $image->temporaryUrl() }}" alt=""
                                 class="aspect-square w-full rounded-xl border border-white/10 object-cover">
                            <span class="absolute left-1 top-1 rounded-md bg-black/70 px-1.5 text-[10px] font-bold">#{{ $i + 1 }}</span>
                            <button type="button" wire:click="removeImage({{ $i }})" aria-label="Buang gambar {{ $i + 1 }}"
                                    class="absolute -right-1.5 -top-1.5 flex h-6 w-6 items-center justify-center rounded-full border border-white/20 bg-ink-900 text-xs font-bold text-white/80">
                                &times;
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (count($images) < config('dynoads.creative.max_images'))
                <label class="mt-3 flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-white/20 bg-white/[0.03] px-4 py-5 text-sm font-semibold text-white/70">
                    <span wire:loading.remove wire:target="upload">
                        {{ $images ? '+ Tambah gambar lagi' : '+ Pilih gambar' }}
                    </span>
                    <span wire:loading wire:target="upload" class="text-dyno-pink">Memuat naik…</span>
                    <input type="file" wire:model="upload" multiple accept="image/*" class="hidden">
                </label>
                <p class="hint">
                    Boleh pilih satu-satu. Setiap gambar ditambah, bukan menggantikan yang sebelumnya.
                    Semua dipotong jadi persegi 1080&times;1080.
                </p>
            @else
                <p class="hint">Sudah cukup 4. Buang satu kalau nak tukar.</p>
            @endif

            @error('images') <p class="err">{{ $message }}</p> @enderror
            @error('images.*') <p class="err">{{ $message }}</p> @enderror
        </div>

        {{-- Masalah --}}
        <div>
            <label class="label" for="problem">Masalah pelanggan anda</label>
            <textarea id="problem" wire:model="problem" rows="2" class="field mt-2"
                      placeholder="Contoh: kira duit lambat waktu peak hour"></textarea>
            @error('problem') <p class="err">{{ $message }}</p> @enderror
        </div>

        {{-- Tawaran --}}
        <div>
            <label class="label" for="offer">Apa yang anda tawarkan</label>
            <textarea id="offer" wire:model="offer" rows="2" class="field mt-2"
                      placeholder="Contoh: sistem POS fullset, pasang di kedai"></textarea>
            @error('offer') <p class="err">{{ $message }}</p> @enderror
        </div>

        {{-- Telefon --}}
        <div>
            <label class="label" for="phone">Nombor WhatsApp</label>
            <input id="phone" type="tel" inputmode="numeric" wire:model="phone" class="field mt-2" placeholder="60187922844">
            <p class="hint">Format 60XXXXXXXXX, tiada tanda +.</p>
            @error('phone') <p class="err">{{ $message }}</p> @enderror
        </div>

        {{-- Kawasan --}}
        <div>
            <span class="label">Kawasan</span>

            <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" wire:click="clearRegions"
                        class="chip {{ $regionKeys === [] ? 'chip-on' : '' }}">
                    Seluruh Malaysia
                </button>

                @foreach ($regions as $region)
                    <button type="button" wire:click="toggleRegion('{{ $region['key'] }}')"
                            class="chip {{ in_array($region['key'], $regionKeys, true) ? 'chip-on' : '' }}">
                        {{ $region['name'] }}
                    </button>
                @endforeach
            </div>

            @if ($regions)
                <p class="hint">
                    Tekan negeri untuk pilih — boleh pilih beberapa. Kalau tiada satu pun dipilih,
                    iklan pergi ke seluruh Malaysia (tidak termasuk Sabah, Sarawak dan Labuan).
                </p>
            @else
                <div class="mt-2 rounded-xl border border-amber-400/25 bg-amber-400/10 p-3 text-xs leading-relaxed text-amber-200/90">
                    Senarai negeri tidak dapat ditarik dari Meta, jadi buat masa ni iklan pergi ke
                    seluruh Malaysia sahaja.
                    @if ($regionError)
                        <span class="mt-1.5 block break-words text-amber-200/60">Sebab: {{ $regionError }}</span>
                    @endif
                    <span class="mt-1.5 block text-amber-200/60">Semak META_ACCESS_TOKEN dalam .env.</span>
                </div>
            @endif
        </div>

        {{-- Bajet --}}
        <div>
            <span class="label">Bajet sehari, setiap iklan</span>
            <div class="mt-2 grid grid-cols-4 gap-2">
                @foreach ([20, 30, 37, 50] as $preset)
                    <button type="button" wire:click="$set('budgetRm', {{ $preset }})"
                            class="chip text-center {{ $budgetRm === $preset ? 'chip-on' : '' }}">
                        RM{{ $preset }}
                    </button>
                @endforeach
            </div>
            <input type="number" wire:model.live="budgetRm" min="10" max="200" class="field mt-2">
            @error('budgetRm') <p class="err">{{ $message }}</p> @enderror

            @if ($images)
                <div class="card mt-3 flex items-center justify-between p-3.5">
                    <span class="text-xs text-white/50">{{ count($images) }} iklan &times; RM{{ $budgetRm }}</span>
                    <span class="text-base font-bold">
                        <span class="bg-dyno-gradient bg-clip-text text-transparent">RM{{ $this->totalDailyRm() }}</span>
                        <span class="text-xs font-medium text-white/40">/hari</span>
                    </span>
                </div>
            @endif
        </div>

        <div>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save,upload">
                <span wire:loading.remove wire:target="save">Teruskan ke semakan</span>
                <span wire:loading wire:target="save">Sedang siapkan…</span>
            </button>
            <p class="mt-3 text-center text-xs text-white/40">Belum ada apa-apa dihantar ke Meta.</p>
        </div>
    </form>
</div>
