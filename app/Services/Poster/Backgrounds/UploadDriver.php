<?php

namespace App\Services\Poster\Backgrounds;

use App\Services\ImageProcessor;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Latar yang peniaga muat naik sendiri — dipotong persegi. */
class UploadDriver implements BackgroundDriver
{
    private ?string $sourcePath = null;

    public function from(string $absolutePath): self
    {
        $clone = clone $this;
        $clone->sourcePath = $absolutePath;

        return $clone;
    }

    public function make(string $mood, string $industry = ''): string
    {
        if (! $this->sourcePath) {
            throw new RuntimeException('Tiada gambar latar dimuat naik.');
        }

        $size = (int) config('dynoads.poster.size');
        $relative = config('dynoads.poster.backgrounds').'/upload-'.sha1_file($this->sourcePath).'.jpg';
        $disk = Storage::disk(config('dynoads.poster.disk'));

        if (! $disk->exists($relative)) {
            $disk->makeDirectory(dirname($relative));
            app(ImageProcessor::class)->squareCrop($this->sourcePath, $disk->path($relative));
        }

        return $relative;
    }
}
