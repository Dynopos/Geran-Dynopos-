<?php

namespace App\Support;

/**
 * Had muat naik yang pelayan BENAR-BENAR benarkan.
 *
 * Peraturan validasi yang lebih longgar daripada php.ini adalah janji yang app
 * tidak boleh tunaikan: PHP menolak fail itu sebelum Laravel sempat melihatnya,
 * dan peniaga dapat kegagalan mentah dan bukan mesej yang boleh difahami.
 */
class Uploads
{
    /** Had sebenar dalam kilobait — yang paling ketat antara dua tetapan PHP. */
    public static function maxKilobytes(int $ceiling = 8192): int
    {
        $limits = array_filter([
            self::toKilobytes((string) ini_get('upload_max_filesize')),
            self::toKilobytes((string) ini_get('post_max_size')),
        ]);

        return $limits === [] ? $ceiling : max(1, min(min($limits), $ceiling));
    }

    /** "8 MB" — untuk dipapar kepada peniaga. */
    public static function maxLabel(): string
    {
        $kb = self::maxKilobytes();

        return $kb >= 1024
            ? rtrim(rtrim(number_format($kb / 1024, 1), '0'), '.').' MB'
            : $kb.' KB';
    }

    /** Adakah had pelayan terlalu ketat untuk gambar telefon biasa? */
    public static function tooTightForPhonePhotos(): bool
    {
        return self::maxKilobytes() < 4096;
    }

    private static function toKilobytes(string $value): ?int
    {
        if (! preg_match('/^\s*(\d+(?:\.\d+)?)\s*([KMG]?)/i', $value, $m)) {
            return null;
        }

        return (int) match (strtoupper($m[2])) {
            'G' => $m[1] * 1024 * 1024,
            'M' => $m[1] * 1024,
            'K' => $m[1],
            default => $m[1] / 1024, // bait
        };
    }
}
