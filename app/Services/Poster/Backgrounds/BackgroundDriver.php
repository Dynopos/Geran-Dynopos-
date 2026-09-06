<?php

namespace App\Services\Poster\Backgrounds;

interface BackgroundDriver
{
    /**
     * Hasilkan satu latar dan pulangkan laluan relatif pada disk poster.
     *
     * Latar sahaja — tiada teks, tiada logo, tiada produk. Peraturan mutlak #7.
     */
    public function make(string $mood, string $industry = ''): string;
}
