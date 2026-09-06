<div>
    <h1 class="text-[28px] font-extrabold leading-tight tracking-tight">Buat poster</h1>
    <p class="mt-1.5 text-sm leading-relaxed t-muted">
        Upload gambar produk anda. Kami buang latarnya dan letak atas suasana yang kemas.
    </p>

    <div class="card mt-4 p-3.5 text-xs leading-relaxed t-muted">
        Gambar produk kekal <span class="font-semibold">gambar sebenar anda</span>. AI hanya buat
        suasana di belakangnya — ia tidak pernah mereka-reka produk yang anda tak jual, dan tidak
        pernah menulis teks pada poster.
    </div>

    @if ($error)
        <div class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-sm text-rose-700 dark:text-rose-200">{{ $error }}</div>
    @endif

    @if ($posterUrl)
        <div class="mt-5">
            <img src="{{ $posterUrl }}" alt="Poster" class="w-full rounded-2xl border border-current/10">
            <p class="hint text-center">
                @if ($adaProduk)
                    {{ $cutoutUsed
                        ? 'Latar gambar produk berjaya dibuang.'
                        : 'Latar gambar terlalu sibuk untuk dibuang, jadi ia dibingkaikan sebagai kad. Untuk hasil terbaik, tangkap gambar atas meja atau dinding kosong.' }}
                @endif
            </p>
            @if ($sudahDalamBakul)
                <div class="mt-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 text-center text-sm font-semibold text-emerald-700 dark:text-emerald-200">
                    Poster ni sudah masuk senarai iklan
                </div>
            @elseif ($bakulPenuh)
                <p class="hint text-center">Senarai iklan sudah penuh ({{ config('dynoads.creative.max_images') }} poster). Buang satu dulu.</p>
            @else
                <button type="button" wire:click="useForAd" class="btn-primary mt-3">
                    Guna poster ni untuk iklan
                </button>
            @endif

            <a href="{{ $posterUrl }}" download class="btn-ghost mt-3 block text-center">Muat turun poster</a>
        </div>
    @endif

    @if ($bakul->isNotEmpty())
        <div class="card mt-5 p-4">
            <div class="flex items-baseline justify-between">
                <span class="label">Senarai iklan</span>
                <span class="text-xs font-semibold t-faint">{{ $bakul->count() }} / {{ config('dynoads.creative.max_images') }}</span>
            </div>

            <div class="mt-3 grid grid-cols-4 gap-2">
                @foreach ($bakul as $job)
                    <div class="relative">
                        <img src="{{ Storage::disk(config('dynoads.poster.disk'))->url($job->output_path) }}" alt=""
                             class="aspect-square w-full rounded-xl border border-current/10 object-cover">
                        <button type="button" wire:click="removeFromBasket({{ $job->id }})" aria-label="Buang poster"
                                class="absolute -right-1.5 -top-1.5 flex h-6 w-6 items-center justify-center rounded-full border border-current/20 text-xs font-bold"
                                style="background-color: rgb(var(--surface-soft))">&times;</button>
                    </div>
                @endforeach
            </div>

            <a href="{{ route('ad-sets.create') }}" wire:navigate class="btn-primary mt-4 block text-center">
                Teruskan buat iklan ({{ $bakul->count() }} poster)
            </a>
        </div>
    @endif

    <form wire:submit="generate" class="mt-6 space-y-7">
        <div>
            <span class="label">Gambar produk <span class="t-faint font-normal">(pilihan)</span></span>
            <label class="mt-2 flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-current/20 bg-current/[0.03] px-4 py-5 text-sm font-semibold t-muted">
                <span wire:loading.remove wire:target="product">{{ $product ? 'Tukar gambar' : '+ Pilih gambar produk' }}</span>
                <span wire:loading wire:target="product" class="text-dyno-magenta dark:text-dyno-pink">Memuat naik…</span>
                <input type="file" wire:model="product" accept="image/*" class="hidden">
            </label>
            @if ($product)
                <img src="{{ $product->temporaryUrl() }}" alt="" class="mt-3 h-28 w-28 rounded-xl border border-current/10 object-cover">
            @elseif ($carriedUrl)
                <div class="mt-3 flex items-center gap-3">
                    <img src="{{ $carriedUrl }}" alt="" class="h-28 w-28 rounded-xl border border-current/10 object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold">Dibawa dari borang iklan</p>
                        <button type="button" wire:click="clearProduct"
                                class="mt-1.5 rounded-lg border border-current/15 px-2.5 py-1 text-[11px] font-semibold t-muted">
                            Buang
                        </button>
                    </div>
                </div>
            @endif
            <p class="hint">Tangkap atas meja atau dinding kosong — latar rata paling senang dibuang.</p>
            @error('product') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="headline">Ayat utama</label>
            <textarea id="headline" wire:model="headline" rows="2" class="field mt-2"
                      placeholder="Kira duit lambat waktu peak hour?"></textarea>
            <p class="hint">Teks panjang dikecilkan automatik — ia takkan terpotong.</p>
            @error('headline') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label" for="kicker">Label kecil</label>
                <input id="kicker" wire:model="kicker" class="field mt-2" placeholder="Promo Ogos">
            </div>
            <div>
                <label class="label" for="price">Harga</label>
                <input id="price" wire:model="price" class="field mt-2" placeholder="RM149">
            </div>
        </div>

        <div>
            <label class="label" for="subline">Ayat sokongan</label>
            <input id="subline" wire:model="subline" class="field mt-2" placeholder="Kami pasang terus di kedai anda.">
        </div>

        <div>
            <label class="label" for="cta">Butang</label>
            <input id="cta" wire:model="cta" class="field mt-2">
            @error('cta') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div>
            <span class="label">Suasana latar</span>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($this->moods() as $key => $description)
                    <button type="button" wire:click="$set('mood', '{{ $key }}')"
                            class="chip {{ $mood === $key ? 'chip-on' : '' }}">{{ ucfirst($key) }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <span class="label">Sumber latar</span>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <button type="button" wire:click="$set('backgroundSource', 'stock')"
                        class="chip text-center {{ $backgroundSource === 'stock' ? 'chip-on' : '' }}">Stock (percuma)</button>
                <button type="button" wire:click="$set('backgroundSource', 'ai')"
                        class="chip text-center {{ $backgroundSource === 'ai' ? 'chip-on' : '' }}">Latar AI</button>
            </div>
            <p class="hint">
                @if ($backgroundSource === 'ai')
                    Perlukan pembekal AI dalam .env. Kalau tiada atau ia gagal, kami guna latar stock —
                    poster tetap terhasil.
                @else
                    Latar dijana di pelayan ini. Tiada API, tiada kos.
                @endif
            </p>
        </div>

        <div>
            <label class="label" for="industry">Jenis perniagaan <span class="t-faint font-normal">(untuk latar AI)</span></label>
            <input id="industry" wire:model="industry" class="field mt-2" placeholder="kedai kopi">
        </div>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="generate,product">
            <span wire:loading.remove wire:target="generate">Jana poster</span>
            <span wire:loading wire:target="generate">Sedang render…</span>
        </button>
    </form>
</div>
