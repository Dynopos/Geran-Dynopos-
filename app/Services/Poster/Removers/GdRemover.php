<?php

namespace App\Services\Poster\Removers;

use Illuminate\Support\Facades\Storage;

/**
 * Pembuang latar tempatan — tiada API, tiada kos, tiada kuota.
 *
 * Kebanyakan peniaga menangkap gambar produk atas meja putih, kertas atau
 * dinding kosong. Untuk gambar begitu, isian banjir dari empat penjuru sudah
 * memadai: hanya latar bersambung di luar produk yang dibuang, jadi warna
 * dalam produk itu sendiri tidak pernah tersentuh.
 *
 * Untuk gambar berlatar sibuk ia tidak dapat berbuat apa-apa — dan dalam kes
 * itu ia pulangkan null dan bukan hasil separuh jadi yang nampak rosak.
 * PosterService kemudian membingkaikan gambar asal sebagai kad, yang nampak
 * disengajakan.
 */
class GdRemover implements BackgroundRemover
{
    /** Berapa jauh dari warna penjuru masih dikira latar. */
    private const TOLERANCE = 34;

    /** Kalau kurang daripada ini dibuang, tiada apa yang berbaloi disimpan. */
    private const MIN_REMOVED = 0.06;

    /** Pecahan piksel sempadan yang mesti sepadan sebelum kita cuba langsung. */
    private const MIN_FLAT_BORDER = 0.86;

    public function cutout(string $absolutePath): ?string
    {
        $relative = config('dynoads.poster.cutouts').'/gd-'.sha1_file($absolutePath).'.png';
        $disk = Storage::disk(config('dynoads.poster.disk'));

        if ($disk->exists($relative)) {
            return $relative;
        }

        if (! $source = $this->open($absolutePath)) {
            return null;
        }

        $w = imagesx($source);
        $h = imagesy($source);

        $canvas = imagecreatetruecolor($w, $h);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopy($canvas, $source, 0, 0, 0, 0, $w, $h);
        imagedestroy($source);

        $reference = $this->cornerColour($canvas, $w, $h);

        // Periksa DULU sama ada latar itu rata. Isian banjir atas gambar
        // berlatar sibuk tidak gagal dengan bersih — ia mengeluarkan hasil
        // bercalar dan berlubang yang nampak lebih teruk daripada gambar asal.
        if (! $this->borderIsFlat($canvas, $w, $h, $reference)) {
            imagedestroy($canvas);

            return null;
        }

        $removed = $this->floodFill($canvas, $w, $h, $reference);

        if ($removed / ($w * $h) < self::MIN_REMOVED) {
            imagedestroy($canvas);

            return null; // Latar sibuk — biar PosterService bingkaikan gambar asal.
        }

        $disk->makeDirectory(dirname($relative));
        imagepng($canvas, $disk->path($relative), 9);
        imagedestroy($canvas);

        return $relative;
    }

    /** Purata empat penjuru — itu latar, kalau ada latar rata. */
    private function cornerColour(\GdImage $im, int $w, int $h): array
    {
        $sum = [0, 0, 0];

        foreach ([[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]] as [$x, $y]) {
            $c = imagecolorat($im, $x, $y);
            $sum[0] += ($c >> 16) & 255;
            $sum[1] += ($c >> 8) & 255;
            $sum[2] += $c & 255;
        }

        return [intdiv($sum[0], 4), intdiv($sum[1], 4), intdiv($sum[2], 4)];
    }

    /**
     * Adakah sempadan gambar satu warna rata?
     *
     * Gambar produk di atas meja putih atau dinding kosong: ya. Gambar yang
     * ditangkap dalam kedai bersepah: tidak — dan dalam kes itu kita langsung
     * tidak menyentuhnya.
     */
    private function borderIsFlat(\GdImage $im, int $w, int $h, array $reference): bool
    {
        $sampled = 0;
        $matched = 0;
        $step = max(1, (int) (min($w, $h) / 120));

        for ($x = 0; $x < $w; $x += $step) {
            foreach ([0, $h - 1] as $y) {
                $sampled++;
                $matched += $this->isBackground($im, $x, $y, $reference) ? 1 : 0;
            }
        }

        for ($y = 0; $y < $h; $y += $step) {
            foreach ([0, $w - 1] as $x) {
                $sampled++;
                $matched += $this->isBackground($im, $x, $y, $reference) ? 1 : 0;
            }
        }

        return $sampled > 0 && ($matched / $sampled) >= self::MIN_FLAT_BORDER;
    }

    private function isBackground(\GdImage $im, int $x, int $y, array $reference): bool
    {
        $c = imagecolorat($im, $x, $y);

        return abs((($c >> 16) & 255) - $reference[0])
            + abs((($c >> 8) & 255) - $reference[1])
            + abs(($c & 255) - $reference[2]) <= self::TOLERANCE * 3;
    }

    /** @return int bilangan piksel yang dijadikan lut sinar */
    private function floodFill(\GdImage $im, int $w, int $h, array $reference): int
    {
        $clear = imagecolorallocatealpha($im, 255, 255, 255, 127);
        $seen = array_fill(0, $w * $h, false);
        $stack = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]];
        $removed = 0;

        while ($stack) {
            [$x, $y] = array_pop($stack);

            if ($x < 0 || $y < 0 || $x >= $w || $y >= $h) {
                continue;
            }

            $i = $y * $w + $x;

            if ($seen[$i]) {
                continue;
            }

            if (! $this->isBackground($im, $x, $y, $reference)) {
                continue;
            }

            $seen[$i] = true;
            imagesetpixel($im, $x, $y, $clear);
            $removed++;

            $stack[] = [$x + 1, $y];
            $stack[] = [$x - 1, $y];
            $stack[] = [$x, $y + 1];
            $stack[] = [$x, $y - 1];
        }

        return $removed;
    }

    private function open(string $path): ?\GdImage
    {
        return match (@getimagesize($path)['mime'] ?? null) {
            'image/jpeg' => @imagecreatefromjpeg($path) ?: null,
            'image/png' => @imagecreatefrompng($path) ?: null,
            'image/webp' => @imagecreatefromwebp($path) ?: null,
            default => null,
        };
    }
}
