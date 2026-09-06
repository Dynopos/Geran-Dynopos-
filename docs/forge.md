# Deploy ke Laravel Forge

Nota untuk site Dyno Ads di server `dynopos`. Domain: **dynoads.my**.

Ganti `<folder-site>` di bawah dengan nama direktori sebenar site anda di Forge
(lihat §0). Ia belum tentu sama dengan nama domain.

---

## 0. Domain dynoads.my

**DNS di pendaftar domain.** Halakan domain ke IP pelayan `dynopos`
(Forge memaparkan IP itu di halaman server):

| Jenis | Nama | Nilai |
|---|---|---|
| A | `@` | IP pelayan |
| A | `www` | IP pelayan |

Sebar DNS ambil beberapa minit hingga beberapa jam. Semak dengan
`dig dynoads.my +short` — bila ia pulangkan IP pelayan, baru teruskan.

**Di Forge.** Ada dua jalan, dan pilihan ini menentukan sama ada deploy script
perlu diubah:

- *Tukar domain site sedia ada* — Site → Settings → Change Site Domain.
  Nginx dikemas kini. **Semak sama ada Forge turut menamakan semula direktori
  site**; kalau ya, mana-mana laluan mutlak (termasuk `DB_DATABASE` di §3B)
  mesti dikemas kini juga.
- *Buat site baharu* `dynoads.my` — lebih bersih, dan `dynoads.on-forge.com`
  boleh dikekalkan sebagai staging. Perlu ulang tetapan `.env` dan deploy script.

Blok deploy script dalam §3 sengaja tidak mengandungi `cd`, jadi ia berfungsi
tanpa perubahan pada mana-mana laluan.

**SSL.** Site → SSL → LetsEncrypt, masukkan `dynoads.my` dan `www.dynoads.my`.
Tunggu DNS sebar dahulu — kalau tidak pengesahan gagal.

**Redirect www.** Selepas SSL, tetapkan satu domain sahaja sebagai utama supaya
`www` dan bukan-`www` tidak dianggap dua tapak berbeza oleh Google. Cara paling
mudah di Forge: Site → Settings → Redirects, `www.dynoads.my` → `dynoads.my`.

---

## 1. Tetapan site

| Medan | Nilai |
|---|---|
| Web Directory | `/public` |
| PHP Version | **8.3 atau 8.4** |
| Repository | `Dynopos/Dynopos` |
| Branch | `main` |

**Jangan pilih PHP 8.5.** Projek ini Laravel 11, yang menyokong PHP 8.2–8.4 secara rasmi.
Site lain dalam server ni guna 8.5 — tetapan PHP di Forge adalah per-site, jadi tukar
untuk site ini sahaja tanpa menyentuh yang lain.

## 2. Kenapa "Forge was unable to install Composer dependencies"

Ralat ini berlaku bila site menunjuk branch yang tiada kod aplikasi.
`composer install` menjalankan `post-autoload-dump` → `php artisan package:discover`,
dan `artisan` perlukan `bootstrap/app.php`. Kalau branch tu cuma ada fail konfigurasi
root, composer gagal sebelum apa-apa sempat dipasang.

Pastikan branch yang dipilih benar-benar mengandungi `bootstrap/app.php`, `app/`,
`config/`, `routes/` dan `composer.lock`.

## 3. Deploy script

Site → **Deployments** → **Deployment Script**.

**Jangan padam baris yang Forge sudah letak di atas.** Bergantung pada sama ada
zero-downtime deploy dihidupkan, Forge menyediakan pembukaan yang berbeza — mod
biasa ada `cd .../nama-site` dan `git pull`, mod zero-downtime tiada (Forge sendiri
sudah clone ke dalam `releases/{id}/` dan menjalankan script dari situ).

Kekalkan pembukaan Forge, kemudian **tambah blok di bawah selepasnya**, sebelum
apa-apa baris composer yang sedia ada:

```bash
# Jaring keselamatan. App juga mencipta folder ini sendiri semasa boot
# (AppServiceProvider), jadi deploy tidak lagi bergantung pada baris ni —
# tetapi ia murah dan menjadikan release pertama bersih.
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/app/public \
         storage/logs \
         bootstrap/cache

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Build CSS/JS. Tanpa ini, layout jatuh ke Tailwind play CDN — muat turun ~100KB
# JS setiap muka surat, dan Tailwind sendiri kata ia bukan untuk production.
npm ci
npm run build

# Fail sqlite duduk dalam storage/ (yang dikongsi antara release), BUKAN dalam
# database/ (yang dicipta semula setiap deploy). Lihat bahagian Database di bawah.
touch storage/app/database.sqlite

$FORGE_PHP artisan migrate --force

# Tanpa ini, gambar iklan langsung tidak dipapar di /semak dan /run.
$FORGE_PHP artisan storage:link

$FORGE_PHP artisan optimize

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

### "Please provide a valid cache path." — sudah dibetulkan dalam kod

Deploy gagal tiga kali dengan ralat ini semasa `composer install` →
`package:discover`. Dua puncanya, dua-dua sudah ditutup:

1. **Config lalai Laravel** guna `realpath(storage_path('framework/views'))`.
   `realpath()` pulangkan `false` bila folder tiada, dan ia dinilai masa config
   dimuat — sebelum apa-apa sempat menciptanya. Kita hantar `config/view.php`
   sendiri tanpa `realpath()`; Blade mencipta folder itu sendiri bila mengkompil.

2. **`AppServiceProvider` kita tidak pernah dijalankan.** Laravel Pint (dev
   dependency) turut mengisytiharkan namespace `App\`, dan classmap composer
   memilih `vendor/laravel/pint/app/Providers/AppServiceProvider.php` dan bukan
   fail kita. `composer.json` kini ada `exclude-from-classmap` untuk laluan itu.
   Provider tu sekarang mencipta setiap folder storage yang hilang semasa boot.

Kalau ralat ini muncul semula, jangan tampal `mkdir` lagi — semak dua perkara di
atas dahulu. `tests/Feature/ViewCachePathTest.php` menjaga kedua-duanya.

### Chromium untuk enjin poster

Poster dirender dengan Playwright. Pelayan perlukan Chromium — sekali sahaja,
melalui Site → **Commands**:

```
npx playwright install --with-deps chromium
```

Jangan letak baris ni dalam deploy script; ia memuat turun ratusan MB setiap kali.
Sekali cukup, dan ia kekal antara deploy.

Kalau Chromium duduk di tempat lain pada pelayan anda, tetapkan laluannya dalam
Environment dan bukan menyalin binari:

```
PLAYWRIGHT_CHROMIUM_PATH=/laluan/ke/chrome
```

Tanpa Chromium, skrin `/poster` akan pulangkan ralat render — bahagian lain app
tidak terjejas.

## 3B. Database — baca ini kalau zero-downtime dihidupkan

Fasa 0 guna sqlite. Laluan lalai Laravel ialah `database/database.sqlite` — dan
dalam mod zero-downtime, folder `database/` adalah **sebahagian daripada release**,
bukan dikongsi. Setiap deploy mencipta release baru, jadi anda dapat fail sqlite
kosong: semua set iklan, variant dan `auto_actions` hilang senyap. Tiada ralat,
tiada amaran. Data cuma hilang.

Hanya `storage/` dan `.env` yang Forge kongsi antara release.

Jadi tunjukkan sqlite ke dalam `storage/`. Dalam tab **Environment**:

```
DB_CONNECTION=sqlite
DB_DATABASE=/home/forge/<folder-site>/storage/app/database.sqlite
```

Laluan mutlak, bukan relatif. Deploy script di atas sudah `touch` fail itu.

**Alternatif yang lebih kemas:** guna MySQL. Forge sudah sediakan pelayan MySQL,
dan Fasa 7 akan bertukar ke MySQL juga — jadi buat sekarang menjimatkan kerja
kemudian. Buat database dalam Server → Database, kemudian:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dynoads
DB_USERNAME=forge
DB_PASSWORD=<dari Forge>
```

Kalau guna MySQL, buang baris `touch storage/app/database.sqlite` dari script.

### Kenapa `npm run build` perlu

`resources/views/layouts/app.blade.php` semak sama ada `public/build/manifest.json`
wujud. Kalau ada, ia guna asset yang dibina (10 KB CSS). Kalau tiada, ia jatuh ke
`https://cdn.tailwindcss.com` — Tailwind play CDN, yang mengkompil CSS dalam browser
pelanggan. Ia berfungsi, tapi lambat dan Tailwind sendiri kata jangan guna di production.

`public/build/` tidak masuk git (sengaja), jadi ia mesti dibina masa deploy.
Server Forge sudah ada Node dan npm.

`artisan optimize` cache config, route dan view. Ia membaca `.env` masa cache dibuat —
jadi **isi `.env` dahulu**, baru tekan Deploy. Kalau tukar `.env` selepas tu, deploy
semula (atau jalankan `php artisan optimize:clear`).

## 4. `.env` di Forge

Site → tab **Environment** → **Edit .env**. Editor terbuka dalam browser; tiada SSH perlu.

Dua baris sahaja yang kosong:

```
META_ACCESS_TOKEN=      # System User token, expiry Never
ANTHROPIC_API_KEY=      # sk-ant-...
```

Yang lain sudah berisi. Tukar juga tiga ini untuk production:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dynoads.my
```

**`APP_URL` mesti tepat.** Gambar iklan dipapar melalui `Storage::url()`, yang
membina URL mutlak dari `APP_URL`. Kalau ia masih `http://localhost:8000`, setiap
gambar dalam `/semak`, `/run` dan `/dashboard` akan pecah — halaman tetap keluar,
cuma gambar tidak.

`APP_DEBUG=true` di production akan memaparkan isi `.env` — termasuk token — pada
mana-mana halaman ralat. Jangan tinggalkan ia hidup.

`APP_KEY` kena diisi. Kalau kosong, jalankan sekali melalui Site → **Commands**:

```
php artisan key:generate --force
```

## 5. Semak selepas deploy

- Buka `https://dynoads.my/buat` — borang patut keluar
- Dropdown **Kawasan** menunjukkan senarai negeri = token Meta berjaya.
  Kekal "Seluruh Malaysia" sahaja = token belum kena.
- Muat naik satu gambar dan teruskan ke `/semak` — kalau gambar tidak papar,
  `storage:link` tidak jalan.

## 6. Nota PHP 8.5

Atas PHP 8.5, log deploy memaparkan amaran ini dari dalam Laravel sendiri:

```
Deprecated: Constant PDO::MYSQL_ATTR_SSL_CA is deprecated since 8.5,
use Pdo\Mysql::ATTR_SSL_CA instead
in vendor/laravel/framework/config/database.php on line 81
```

Ia **notice, bukan ralat** — ia tidak menggagalkan deploy dan tidak dipapar kepada
pengguna bila `APP_DEBUG=false`. Tetapi ia datang dari kod rangka kerja yang kita
tidak boleh betulkan sendiri, dan ia akan memenuhi `storage/logs` setiap request.

Laravel 11 menyokong PHP 8.2–8.4 secara rasmi. Kalau amaran ni mengganggu, pasang
PHP 8.4 pada server (Server → PHP → pasang versi baru) dan tukar site ini sahaja
kepada 8.4 — versi PHP dipilih per-site, jadi site lain tidak terjejas.

## 7. Kebenaran fail

Kalau ada ralat "failed to open stream: Permission denied" pada `storage/` atau
`database/database.sqlite`, jalankan melalui Site → **Commands**:

```
chmod -R 775 storage bootstrap/cache database
```
