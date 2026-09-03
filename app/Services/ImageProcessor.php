<?php

namespace App\Services;

use RuntimeException;

/**
 * Crop tengah 1080×1080 guna GD. Tiada teks ditambah di sini —
 * peraturan mutlak #7 (teks poster datang dari HTML, bukan dari gambar).
 */
class ImageProcessor
{
    public function __construct(protected int $size = 1080) {}

    /**
     * Crop tengah dan simpan sebagai JPEG.
     *
     * @return string laluan penuh fail yang disimpan
     */
    public function squareCrop(string $sourcePath, string $destinationPath): string
    {
        $image = $this->open($sourcePath);
        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);

        $canvas = imagecreatetruecolor($this->size, $this->size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        imagecopyresampled(
            $canvas, $image,
            0, 0,
            (int) (($width - $side) / 2), (int) (($height - $side) / 2),
            $this->size, $this->size,
            $side, $side
        );

        if (! is_dir($dir = dirname($destinationPath))) {
            mkdir($dir, 0775, true);
        }

        imagejpeg($canvas, $destinationPath, 90);
        imagedestroy($canvas);
        imagedestroy($image);

        return $destinationPath;
    }

    protected function open(string $path): \GdImage
    {
        if (! is_file($path)) {
            throw new RuntimeException("Gambar tidak dijumpai: {$path}");
        }

        $info = @getimagesize($path);

        $image = match ($info['mime'] ?? null) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };

        if (! $image) {
            throw new RuntimeException('Format gambar tidak disokong. Guna JPG, PNG atau WEBP.');
        }

        return $image;
    }
}
