<?php

namespace App\Services\Poster\Backgrounds;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Latar dari pembekal imej AI.
 *
 * Peraturan mutlak #7: prompt WAJIB meminta gambar tanpa teks, logo atau papan
 * tanda. Model imej tidak boleh dipercayai mengeja Melayu — jadi kita tidak
 * benarkan ia mencuba. Semua teks datang dari HTML.
 *
 * Pembekal dikonfigur dalam config/dynoads.php supaya boleh ditukar tanpa
 * mengubah kod. Kegagalan jatuh ke StockDriver — peniaga tetap dapat poster.
 */
class AiDriver implements BackgroundDriver
{
    public function __construct(private StockDriver $fallback) {}

    public function make(string $mood, string $industry = ''): string
    {
        $config = config('dynoads.poster.background');

        if (blank($config['endpoint']) || blank($config['api_key'])) {
            return $this->fallback->make($mood, $industry);
        }

        // Latar yang sama untuk industri + mood yang sama boleh diguna semula.
        $relative = sprintf(
            '%s/ai-%s.jpg',
            config('dynoads.poster.backgrounds'),
            sha1($mood.'|'.$industry.'|'.$config['model'])
        );

        $disk = Storage::disk(config('dynoads.poster.disk'));

        if ($disk->exists($relative)) {
            return $relative;
        }

        try {
            $binary = $this->request($this->prompt($mood, $industry), $config);
        } catch (\Throwable $e) {
            // Jangan halang peniaga sebab pembekal AI down.
            Log::warning('Latar AI gagal, guna stock.', ['ralat' => $e->getMessage()]);

            return $this->fallback->make($mood, $industry);
        }

        $disk->makeDirectory(dirname($relative));
        $disk->put($relative, $binary);

        return $relative;
    }

    public function prompt(string $mood, string $industry = ''): string
    {
        $moods = config('dynoads.poster.background.moods');
        $scene = $moods[$mood] ?? $moods['studio'];

        return trim(($industry !== '' ? "For a {$industry} business. " : '').$scene.'. '
            .config('dynoads.poster.background.prompt_suffix'));
    }

    private function request(string $prompt, array $config): string
    {
        $size = (int) config('dynoads.poster.size');

        $response = Http::timeout((int) $config['timeout'])
            ->withHeaders([$config['api_key_header'] => $config['api_key']])
            ->post($config['endpoint'], array_filter([
                'model' => $config['model'],
                'prompt' => $prompt,
                'size' => "{$size}x{$size}",
                'n' => 1,
            ]));

        if ($response->failed()) {
            throw new RuntimeException('Pembekal latar AI pulangkan '.$response->status().'.');
        }

        // Terima sama ada imej mentah, URL, atau base64 — bentuk balasan
        // berbeza antara pembekal.
        if (str_contains((string) $response->header('Content-Type'), 'image/')) {
            return $response->body();
        }

        $body = (array) $response->json();

        if ($b64 = data_get($body, 'data.0.b64_json') ?? data_get($body, 'image')) {
            return base64_decode($b64, true) ?: throw new RuntimeException('Base64 tidak sah dari pembekal.');
        }

        if ($url = data_get($body, 'data.0.url') ?? data_get($body, 'url')) {
            $image = Http::timeout((int) $config['timeout'])->get($url);

            if ($image->failed()) {
                throw new RuntimeException('Gagal muat turun latar dari '.parse_url($url, PHP_URL_HOST).'.');
            }

            return $image->body();
        }

        throw new RuntimeException('Balasan pembekal latar AI tidak dikenali.');
    }
}
