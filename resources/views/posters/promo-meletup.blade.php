{{--
    Template poster. SEMUA teks di sini dihasilkan oleh HTML, tidak pernah oleh
    model imej — peraturan mutlak #7. Latar adalah gambar; produk adalah gambar
    peniaga sendiri. Teks duduk di atas scrim gelap supaya sentiasa terbaca.
--}}
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0 }
    * { margin: 0; padding: 0; box-sizing: border-box }

    html, body {
        width: {{ $size }}px;
        height: {{ $size }}px;
        overflow: hidden;
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .poster { position: relative; width: 100%; height: 100%; background: #111 }

    .bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover }

    /* Scrim: teks kekal terbaca walau latar apa pun. */
    .scrim {
        position: absolute; inset: 0;
        background:
            linear-gradient(180deg, rgba(6,4,14,.72) 0%, rgba(6,4,14,.18) 34%, rgba(6,4,14,.10) 52%, rgba(6,4,14,.86) 100%);
    }

    /*
       Produk peniaga — gambar sebenar mereka, bukan janaan AI.
       Ia duduk dalam jalur tengah yang tidak pernah bertindih dengan teks:
       headline di atas, harga dan CTA di bawah, produk di antaranya.
    */
    .zon-produk {
        position: absolute;
        left: {{ $pad }}px; right: {{ $pad }}px;
        top: 30%; bottom: 34%;
        display: flex; align-items: center; justify-content: center;
    }

    .produk { max-width: 100%; max-height: 100%; object-fit: contain }

    /* Latar sudah dibuang: biar ia terapung dengan bayang sendiri. */
    .produk.potong {
        filter: drop-shadow(0 {{ (int) ($size * 0.020) }}px {{ (int) ($size * 0.028) }}px rgba(0,0,0,.5));
    }

    /*
       Latar tidak dapat dibuang (gambar berlatar sibuk). Bingkaikan sebagai kad
       supaya ia nampak disengajakan, bukan seperti tampalan yang tersasar.
    */
    .produk.kad {
        border-radius: {{ (int) ($size * 0.026) }}px;
        border: {{ max(2, (int) ($size * 0.004)) }}px solid rgba(255,255,255,.85);
        box-shadow: 0 {{ (int) ($size * 0.016) }}px {{ (int) ($size * 0.036) }}px rgba(0,0,0,.45);
        object-fit: cover;
        width: 62%; height: 100%;
    }

    .atas { position: absolute; left: {{ $pad }}px; right: {{ $pad }}px; top: {{ $pad }}px; max-height: 26% }
    .bawah { position: absolute; left: {{ $pad }}px; right: {{ $pad }}px; bottom: {{ $pad }}px }

    .kicker {
        display: inline-block;
        padding: {{ (int) ($size * 0.011) }}px {{ (int) ($size * 0.024) }}px;
        border-radius: 999px;
        background: linear-gradient(100deg, #1f9cf0, #8b3fd6 55%, #e6248f);
        color: #fff;
        font-size: {{ (int) ($size * 0.030) }}px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .headline {
        margin-top: {{ (int) ($size * 0.022) }}px;
        color: #fff;
        font-weight: 900;
        line-height: 1.04;
        letter-spacing: -.02em;
        text-wrap: balance;
        text-shadow: 0 {{ (int) ($size * 0.004) }}px {{ (int) ($size * 0.014) }}px rgba(0,0,0,.5);
    }

    .subline {
        margin-top: {{ (int) ($size * 0.018) }}px;
        color: rgba(255,255,255,.9);
        font-weight: 600;
        line-height: 1.3;
    }

    .baris-bawah { display: flex; align-items: flex-end; justify-content: space-between; gap: {{ (int) ($size * 0.03) }}px }

    .harga { color: #fff; font-weight: 900; line-height: 1; letter-spacing: -.02em }
    .harga small { display: block; font-size: {{ (int) ($size * 0.026) }}px; font-weight: 700; opacity: .65; letter-spacing: .06em; text-transform: uppercase; margin-bottom: {{ (int) ($size * 0.008) }}px }

    .cta {
        flex-shrink: 0;
        padding: {{ (int) ($size * 0.020) }}px {{ (int) ($size * 0.034) }}px;
        border-radius: {{ (int) ($size * 0.018) }}px;
        background: #25D366;
        color: #04220f;
        font-weight: 900;
        font-size: {{ (int) ($size * 0.030) }}px;
        white-space: nowrap;
    }
</style>
</head>
<body>
<div class="poster">
    <img class="bg" src="{{ $backgroundUrl }}" alt="">
    <div class="scrim"></div>

    @if ($productUrl)
        <div class="zon-produk">
            <img class="produk {{ $isCutout ? 'potong' : 'kad' }}" src="{{ $productUrl }}" alt="">
        </div>
    @endif

    <div class="atas">
        @if ($kicker)
            <span class="kicker">{{ $kicker }}</span>
        @endif
        <div class="headline" style="font-size: {{ fit($headline, 34, $size, 0.078, 0.042) }}px">{{ $headline }}</div>
    </div>

    <div class="bawah">
        @if ($subline)
            <div class="subline" style="font-size: {{ fit($subline, 60, $size, 0.038, 0.026) }}px">{{ $subline }}</div>
        @endif

        <div class="baris-bawah" style="margin-top: {{ (int) ($size * 0.028) }}px">
            @if ($price)
                <div class="harga" style="font-size: {{ fit($price, 12, $size, 0.082, 0.05) }}px">
                    <small>Mulai</small>{{ $price }}
                </div>
            @else
                <div></div>
            @endif
            <div class="cta">{{ $cta }}</div>
        </div>
    </div>
</div>
</body>
</html>
