<?php

if (! function_exists('fit')) {
    /**
     * Saiz font yang menjamin teks muat — TEKS TIDAK BOLEH TERPOTONG.
     *
     * Poster ini dilihat oleh peniaga sebagai hasil siap; kalau headline
     * terpotong di tengah perkataan, mereka hilang kepercayaan pada app.
     * Jadi daripada memotong teks, kita kecilkan font sehingga ia muat, dan
     * tidak pernah lebih kecil daripada had bawah yang masih boleh dibaca
     * atas telefon.
     *
     * @param  string  $text  teks yang akan dipapar
     * @param  int  $comfy  bilangan aksara yang muat pada saiz penuh
     * @param  int  $canvas  lebar poster dalam piksel
     * @param  float  $maxRatio  saiz penuh sebagai pecahan lebar poster
     * @param  float  $minRatio  saiz terkecil yang masih boleh dibaca
     */
    function fit(?string $text, int $comfy, int $canvas, float $maxRatio, float $minRatio): int
    {
        $length = mb_strlen(trim((string) $text));

        if ($length === 0 || $length <= $comfy) {
            return (int) round($canvas * $maxRatio);
        }

        // Luas teks lebih kurang berkadar dengan kuasa dua saiz font, jadi
        // skala mengikut punca kuasa dua nisbah lebihan.
        $scale = sqrt($comfy / $length);
        $ratio = max($minRatio, $maxRatio * $scale);

        return (int) round($canvas * $ratio);
    }
}
