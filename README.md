# Dyno Ads — Fasa 0 (Create Post)

Auto-create iklan Meta **Click-to-WhatsApp** untuk peniaga kecil. Produk di bawah ekosistem DYNOPRO.

**Fasa 0 = alat single-account untuk pemilik.** Tiada login, tiada OAuth, tiada bayaran.
Token duduk dalam `.env`. Fasa 1 (auto split test), Fasa 2 (multi-user) dan Fasa 3 (jual)
diterangkan dalam [`CLAUDE.md`](CLAUDE.md).

---

## Flow

```
/buat        Upload 1–4 gambar → masalah → tawaran → telefon → kawasan → bajet
/semak/{id}  Caption AI (boleh edit) + pratonton → Approve → campaign dibuat PAUSED
/run/{id}    Butang RUN SEMUA → campaign/adset/ad jadi ACTIVE. Pause per iklan.
/dashboard/{id}  Spend, lead WhatsApp, kos/lead setiap iklan + rekod tindakan
```

Satu gambar = satu campaign. Itu asas split test: kita bandingkan gambar,
bukan bandingkan adset dalam satu campaign.

---

## Setup

```bash
git clone https://github.com/Dynopos/dynoads.git
cd dynoads

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite      # Fasa 0 guna sqlite
php artisan migrate
php artisan storage:link            # supaya gambar boleh dipapar

php artisan serve                   # http://localhost:8000
```

Frontend guna Tailwind CDN secara lalai — tak perlu `npm` untuk mula.
Kalau nak build sendiri (lebih laju, saiz kecil):

```bash
npm install && npm run build        # layout auto-guna build kalau manifest wujud
```

### `.env` yang perlu diisi

| Kunci | Contoh | Nota |
|---|---|---|
| `META_ACCESS_TOKEN` | `EAAB…` | System User token, expiry **Never**. Jangan commit. |
| `META_AD_ACCOUNT_ID` | `act_701202512896650` | Prefix `act_` ditambah automatik kalau tertinggal |
| `META_PAGE_ID` | `377330642350146` | Page yang linked dengan nombor WhatsApp |
| `META_WA_PHONE` | `60187922844` | Format antarabangsa, tiada `+` |
| `META_API_VERSION` | `v21.0` | Versi dipin — jangan auto-upgrade |
| `ANTHROPIC_API_KEY` | `sk-ant-…` | Untuk caption |

---

## Peraturan mutlak (dikuatkuasakan dalam kod)

1. `MetaAdsService` **tiada** method `update*` — nak ubah campaign = buat baru.
2. `AdVariant::isOwnedByApp()` menapis setiap pause/run. Campaign yang bukan app ni buat
   tidak akan disentuh — ia langsung tiada dalam jadual `ad_variants`.
3. Semua campaign/adset/ad dibuat `PAUSED`. Hanya butang RUN yang menghidupkannya.
4. Setiap tindakan direkod dalam `auto_actions` **sebelum** panggilan API dibuat.
5. Token hanya dalam `.env`; `MetaApiException` sengaja tidak memuatkan token dalam mesej.

---

## Setting Meta yang terbukti

Dari 150+ campaign sejarah DynoPOS (kos/lead ~RM10–15). Semuanya duduk dalam
[`config/dynoads.php`](config/dynoads.php) — satu tempat, senang audit.

| Lapisan | Setting |
|---|---|
| Campaign | `OUTCOME_ENGAGEMENT`, budget di campaign (CBO), `LOWEST_COST_WITHOUT_CAP`, `special_ad_categories=[]` |
| Ad set | `CONVERSATIONS`, `destination_type=WHATSAPP`, `billing_event=IMPRESSIONS`, `promoted_object={page_id, whatsapp_phone_number}` |
| Targeting | umur 25–65, `locales=[41]`, `advantage_audience=1`, `location_types=[home,recent]`, brand safety RELAXED |
| Seluruh Malaysia | `countries=[MY]`, exclude region 2546 Sarawak / 2550 Labuan / 2551 Sabah |
| Negeri tertentu | `geo_locations.regions=[{key}]` |
| Ad | link ad, `WHATSAPP_MESSAGE`, `link=https://api.whatsapp.com/send`, gambar via `image_hash` |
| Bajet lalai | RM37/hari (3700 sen) |
| Nama | `DYNOADS-{setID}-{n}-{DDMMMYY}` |

> ⚠️ Satu field perlu disahkan pada akaun sebenar: bila pilih negeri tertentu, kod
> menghantar `targeting_automation.individual_setting.geo_locations = 1` (mengikut setup
> manual). Kalau Meta pulangkan ralat parameter, buang baris tu dalam
> `MetaAdsService::buildTargeting()` — tiada kesan lain.

---

## Test

```bash
php artisan test
```

Semua panggilan luar guna `Http::fake()` — test tidak sentuh Meta atau Claude.
Yang diuji: payload campaign/adset/ad, targeting geo, parsing insights
(`onsite_conversion.total_messaging_connection`), pengendalian `error_user_msg`,
retry JSON caption, penapis perkataan larangan, dan penguatkuasaan "hanya sentuh
campaign sendiri".

CI (GitHub Actions) jalankan Pint + test pada setiap push.

---

## Semak sebelum tutup Fasa 0

- [ ] 4 campaign muncul PAUSED dalam Ads Manager dengan nama `DYNOADS-…`
- [ ] Tekan RUN → semua jadi ACTIVE dalam Ads Manager
- [ ] Refresh dashboard → spend & lead sepadan dengan Ads Manager
- [ ] Tiada campaign lain dalam akaun tersentuh

## Struktur

```
app/Services/MetaAdsService.php    semua panggilan Graph API
app/Services/CaptionService.php    caption AI (takut → tenang)
app/Services/AdLauncher.php        orkestra DB ⇄ Meta, log auto_actions
app/Services/ImageProcessor.php    crop tengah 1080×1080 (GD)
app/Livewire/AdSets/               Create, Review, Run, Dashboard
config/dynoads.php                 semua setting Meta yang terbukti
docs/dyno-ads-spec.md              spec produk penuh
```
