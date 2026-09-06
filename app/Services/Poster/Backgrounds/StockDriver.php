<?php

namespace App\Services\Poster\Backgrounds;

use Illuminate\Support\Facades\Storage;

/**
 * Latar yang dijana secara tempatan dengan GD.
 *
 * Tiada API, tiada kos, tiada kuota — jadi enjin poster berfungsi sepenuhnya
 * sebelum mana-mana pembekal AI disambungkan, dan ia menjadi tempat jatuh bila
 * AI gagal. Peniaga dapat poster, bukan mesej ralat.
 */
class StockDriver implements BackgroundDriver
{
    /** Palet lembut yang tidak melawan produk di hadapannya. */
    private const PALETTES = [
        'kedai' => [[247, 240, 230], [214, 190, 160]],
        'kafe' => [[240, 233, 226], [176, 152, 130]],
        'studio' => [[246, 246, 250], [206, 206, 220]],
        'meja' => [[242, 244, 248], [198, 206, 220]],
        'dapur' => [[240, 246, 246], [190, 208, 208]],
    ];

    public function make(string $mood, string $industry = ''): string
    {
        $size = (int) config('dynoads.poster.size');
        [$top, $bottom] = self::PALETTES[$mood] ?? self::PALETTES['studio'];

        $im = imagecreatetruecolor($size, $size);

        // Gradien menegak — cahaya dari atas, jadi produk nampak berpijak.
        for ($y = 0; $y < $size; $y++) {
            $t = $y / $size;
            imagefilledrectangle($im, 0, $y, $size, $y, imagecolorallocate($im,
                (int) ($top[0] + ($bottom[0] - $top[0]) * $t),
                (int) ($top[1] + ($bottom[1] - $top[1]) * $t),
                (int) ($top[2] + ($bottom[2] - $top[2]) * $t),
            ));
        }

        // Bayang sokongan halus di bawah tengah, supaya produk tidak terapung.
        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 108);
        imagefilledellipse($im, (int) ($size / 2), (int) ($size * 0.74), (int) ($size * 0.62), (int) ($size * 0.09), $shadow);

        $relative = config('dynoads.poster.backgrounds').'/stock-'.$mood.'-'.$size.'.jpg';
        $disk = Storage::disk(config('dynoads.poster.disk'));
        $disk->makeDirectory(dirname($relative));

        imagejpeg($im, $disk->path($relative), 92);
        imagedestroy($im);

        return $relative;
    }
}
