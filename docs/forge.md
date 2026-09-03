# Deploy ke Laravel Forge

Nota untuk site `dynoads.on-forge.com` di server `dynopos`.

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

Site → **Deployments** → **Deployment Script**. Ganti isi dengan ini:

```bash
cd /home/forge/dynoads.on-forge.com
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Fasa 0 guna sqlite — fail DB tidak masuk git, jadi cipta kalau belum ada.
touch database/database.sqlite

$FORGE_PHP artisan migrate --force

# Tanpa ini, gambar iklan langsung tidak dipapar di /semak dan /run.
$FORGE_PHP artisan storage:link

$FORGE_PHP artisan optimize

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

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

Yang lain sudah berisi. Tukar juga dua ini untuk production:

```
APP_ENV=production
APP_DEBUG=false
```

`APP_DEBUG=true` di production akan memaparkan isi `.env` — termasuk token — pada
mana-mana halaman ralat. Jangan tinggalkan ia hidup.

`APP_KEY` kena diisi. Kalau kosong, jalankan sekali melalui Site → **Commands**:

```
php artisan key:generate --force
```

## 5. Semak selepas deploy

- Buka `https://dynoads.on-forge.com/buat` — borang patut keluar
- Dropdown **Kawasan** menunjukkan senarai negeri = token Meta berjaya.
  Kekal "Seluruh Malaysia" sahaja = token belum kena.
- Muat naik satu gambar dan teruskan ke `/semak` — kalau gambar tidak papar,
  `storage:link` tidak jalan.

## 6. Kebenaran fail

Kalau ada ralat "failed to open stream: Permission denied" pada `storage/` atau
`database/database.sqlite`, jalankan melalui Site → **Commands**:

```
chmod -R 775 storage bootstrap/cache database
```
