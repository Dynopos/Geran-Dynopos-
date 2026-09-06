<?php

namespace App\Services\Poster\Removers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pembuang latar melalui API HTTP.
 *
 * Endpoint, header kunci dan nama medan fail semuanya dari config, supaya
 * pembekal boleh ditukar tanpa mengubah kod. Kegagalan pulangkan null —
 * poster tetap terhasil dengan gambar asal.
 */
class HttpRemover implements BackgroundRemover
{
    public function cutout(string $absolutePath): ?string
    {
        $config = config('dynoads.poster.remover');

        if (blank($config['endpoint'])) {
            return null;
        }

        $relative = config('dynoads.poster.cutouts').'/'.sha1_file($absolutePath).'.png';
        $disk = Storage::disk(config('dynoads.poster.disk'));

        if ($disk->exists($relative)) {
            return $relative;
        }

        try {
            $response = Http::timeout((int) $config['timeout'])
                ->withHeaders(array_filter([$config['api_key_header'] => $config['api_key']]))
                ->attach($config['file_field'], file_get_contents($absolutePath), basename($absolutePath))
                ->post($config['endpoint'], ['format' => 'png']);

            if ($response->failed()) {
                throw new RuntimeException('Pembuang latar pulangkan '.$response->status().'.');
            }

            $binary = str_contains((string) $response->header('Content-Type'), 'image/')
                ? $response->body()
                : base64_decode((string) (data_get($response->json(), 'data.0.b64_json') ?? data_get($response->json(), 'image')), true);

            if (! $binary) {
                throw new RuntimeException('Balasan pembuang latar tidak mengandungi imej.');
            }
        } catch (\Throwable $e) {
            Log::warning('Buang latar gagal, guna gambar asal.', ['ralat' => $e->getMessage()]);

            return null;
        }

        $disk->makeDirectory(dirname($relative));
        $disk->put($relative, $binary);

        return $relative;
    }
}
