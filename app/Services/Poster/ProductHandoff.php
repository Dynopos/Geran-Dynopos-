<?php

namespace App\Services\Poster;

use App\Services\ImageProcessor;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Membawa satu gambar produk dari /buat ke /poster.
 *
 * Tanpa ini, peniaga yang sudah memuat naik gambar di borang iklan terpaksa
 * memuat naiknya semula di skrin poster. Fail sementara Livewire tidak kekal
 * merentas permintaan, jadi gambar itu disalin ke storan dahulu.
 */
class ProductHandoff
{
    private const KEY = 'dynoads.produk-poster';

    private const FOLDER = 'poster-sumber';

    /** @return string laluan relatif pada disk poster */
    public function put(string $absolutePath): string
    {
        $relative = self::FOLDER.'/'.Str::uuid().'.jpg';
        $disk = $this->disk();

        $disk->makeDirectory(self::FOLDER);
        app(ImageProcessor::class)->squareCrop($absolutePath, $disk->path($relative));

        Session::put(self::KEY, $relative);

        return $relative;
    }

    public function path(): ?string
    {
        $relative = Session::get(self::KEY);

        return $relative && $this->disk()->exists($relative) ? $relative : null;
    }

    public function absolutePath(): ?string
    {
        return ($relative = $this->path()) ? $this->disk()->path($relative) : null;
    }

    public function url(): ?string
    {
        return ($relative = $this->path()) ? $this->disk()->url($relative) : null;
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }

    private function disk()
    {
        return Storage::disk(config('dynoads.poster.disk'));
    }
}
