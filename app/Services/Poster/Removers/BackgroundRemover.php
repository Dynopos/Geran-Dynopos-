<?php

namespace App\Services\Poster\Removers;

interface BackgroundRemover
{
    /**
     * Buang latar gambar produk dan pulangkan laluan PNG lut sinar
     * (relatif pada disk poster), atau null kalau tiada pembuang dikonfigur.
     */
    public function cutout(string $absolutePath): ?string;
}
