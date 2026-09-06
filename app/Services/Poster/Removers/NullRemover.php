<?php

namespace App\Services\Poster\Removers;

/**
 * Tiada pembuang latar dikonfigur.
 *
 * Poster tetap terhasil — gambar produk digunakan seadanya. Lebih baik daripada
 * menghalang peniaga sebab satu API belum disambung.
 */
class NullRemover implements BackgroundRemover
{
    public function cutout(string $absolutePath): ?string
    {
        return null;
    }
}
