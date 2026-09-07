<?php

namespace App\Services\Poster;

use App\Models\PosterJob;
use App\Services\Poster\Backgrounds\AiDriver;
use App\Services\Poster\Backgrounds\BackgroundDriver;
use App\Services\Poster\Backgrounds\StockDriver;
use App\Services\Poster\Removers\BackgroundRemover;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Data teks + gambar produk → poster PNG 1080×1080.
 *
 * Pembahagian kerja yang tidak boleh dilanggar (peraturan mutlak #7):
 *   AI     → latar/suasana sahaja
 *   Peniaga → gambar produk sebenar mereka
 *   HTML   → setiap patah teks
 *
 * Model imej tidak pernah menulis teks, dan tidak pernah mereka-reka produk
 * yang peniaga tidak jual.
 */
class PosterService
{
    public function __construct(
        private BackgroundRemover $remover,
        private StockDriver $stock,
        private AiDriver $ai,
    ) {}

    /**
     * @param  array{headline:string,subline?:string,price?:string,cta?:string,kicker?:string,industry?:string}  $data
     */
    public function render(
        string $template,
        array $data,
        string $backgroundSource = 'stock',
        string $mood = 'studio',
        ?string $productAbsolutePath = null,
    ): PosterJob {
        $cacheKey = $this->cacheKey($template, $data, $backgroundSource, $mood, $productAbsolutePath);

        $job = PosterJob::firstOrNew(['cache_key' => $cacheKey]);

        // Input sama = poster sama. Jangan panggil Playwright dua kali.
        if ($job->exists && $job->isDone() && $this->disk()->exists($job->output_path)) {
            return $job;
        }

        $job->fill([
            'template' => $template,
            'data' => $data,
            'background_source' => $backgroundSource,
            'background_mood' => $mood,
            'status' => 'pending',
            'last_error' => null,
        ])->save();

        try {
            $cutout = $productAbsolutePath ? $this->remover->cutout($productAbsolutePath) : null;
            $background = $this->driver($backgroundSource)->make($mood, $data['industry'] ?? '');

            $output = $this->renderToPng($template, $data, $background, $cutout, $productAbsolutePath, $cacheKey);

            $job->fill([
                'product_path' => $productAbsolutePath ? basename($productAbsolutePath) : null,
                'cutout_path' => $cutout,
                'background_path' => $background,
                'output_path' => $output,
                'status' => 'done',
            ])->save();
        } catch (\Throwable $e) {
            $job->fill(['status' => 'failed', 'last_error' => $e->getMessage()])->save();

            throw $e;
        }

        return $job;
    }

    public function cacheKey(string $template, array $data, string $source, string $mood, ?string $product): string
    {
        ksort($data);

        return sha1(implode('|', [
            $template,
            json_encode($data),
            $source,
            $mood,
            $product && is_file($product) ? sha1_file($product) : '',
        ]));
    }

    private function driver(string $source): BackgroundDriver
    {
        return $source === 'ai' ? $this->ai : $this->stock;
    }

    private function renderToPng(
        string $template,
        array $data,
        string $background,
        ?string $cutout,
        ?string $productAbsolutePath,
        string $cacheKey,
    ): string {
        $size = (int) config('dynoads.poster.size');
        $disk = $this->disk();

        // Playwright memuat HTML melalui file://, jadi gambar dirujuk sebagai
        // laluan fail mutlak — bukan URL. Ia berfungsi tanpa pelayan web hidup.
        $productUrl = $cutout
            ? 'file://'.$disk->path($cutout)
            : ($productAbsolutePath ? 'file://'.$productAbsolutePath : null);

        $html = Blade::render(
            file_get_contents(resource_path("views/posters/{$template}.blade.php")),
            [
                'size' => $size,
                'pad' => (int) ($size * 0.072),
                'backgroundUrl' => 'file://'.$disk->path($background),
                'productUrl' => $productUrl,
                'isCutout' => (bool) $cutout,
                'headline' => (string) ($data['headline'] ?? ''),
                'subline' => (string) ($data['subline'] ?? ''),
                'price' => (string) ($data['price'] ?? ''),
                'kicker' => (string) ($data['kicker'] ?? ''),
                'cta' => (string) ($data['cta'] ?? 'WhatsApp kami'),
            ]
        );

        $htmlPath = sys_get_temp_dir()."/dynoads-poster-{$cacheKey}.html";
        file_put_contents($htmlPath, $html);

        $relative = config('dynoads.poster.path')."/{$cacheKey}.png";
        $disk->makeDirectory(dirname($relative));

        try {
            $this->runRenderer($htmlPath, $disk->path($relative), $size);
        } finally {
            @unlink($htmlPath);
        }

        if (! $disk->exists($relative)) {
            throw new RuntimeException('Poster tidak terhasil — Playwright tidak menulis fail.');
        }

        return $relative;
    }

    private function runRenderer(string $htmlPath, string $outputPath, int $size): void
    {
        $renderer = (string) config('dynoads.poster.renderer');

        if (! is_file($renderer)) {
            throw new RuntimeException('Skrip render poster tiada pada pelayan.');
        }

        $process = new Process(
            ['node', $renderer, $htmlPath, $outputPath, (string) $size],
            base_path(),
            // Chromium sudah dipasang; jangan sesekali muat turun semasa render.
            ['PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD' => '1'] + array_filter([
                'PLAYWRIGHT_BROWSERS_PATH' => env('PLAYWRIGHT_BROWSERS_PATH'),
                'PLAYWRIGHT_CHROMIUM_PATH' => env('PLAYWRIGHT_CHROMIUM_PATH'),
            ]),
            null,
            $this->renderTimeout(),
        );

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new RuntimeException(
                'Render poster ambil masa lebih '.(int) $this->renderTimeout().' saat dan dihentikan. '
                .'Chromium mungkin belum dipasang pada pelayan.'
            );
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Render poster gagal: '.$this->explain(
                trim($process->getErrorOutput() ?: $process->getOutput())
            ));
        }
    }

    /**
     * Berapa lama render dibenarkan berjalan.
     *
     * MESTI berakhir sebelum PHP membunuh permintaan. Kalau tidak, PHP mati
     * dahulu dengan ralat 500 mentah — Livewire memaparkan halaman ralat penuh
     * dalam iframe, dan peniaga nampak kotak hitam dan bukan ayat yang
     * menerangkan apa yang berlaku.
     */
    private function renderTimeout(): float
    {
        $wanted = (float) config('dynoads.poster.render_timeout');
        $phpLimit = (float) ini_get('max_execution_time');

        // 0 bermakna tiada had (CLI, atau FPM yang dikonfigur begitu).
        if ($phpLimit <= 0) {
            return $wanted;
        }

        // Sisakan ruang untuk menyimpan fail dan memulangkan balasan.
        return max(5.0, min($wanted, $phpLimit - 5));
    }

    /** Ayat yang berguna dari output Node, bukan longgokan stack trace. */
    public function explain(string $output): string
    {
        $lower = mb_strtolower($output);

        return match (true) {
            str_contains($lower, 'tidak jumpa chrome'),
            str_contains($lower, "executable doesn't exist"),
            str_contains($lower, 'please run the following command'),
            str_contains($lower, 'browsertype.launch') => 'Chrome atau Chromium tiada pada pelayan. '
                .'Pasang sebagai root: apt-get install -y google-chrome-stable. '
                .'Laluan yang diperiksa tersenarai dalam log.',
            str_contains($lower, 'cannot find module') => 'Pakej Node tiada. Jalankan npm ci pada pelayan.',
            str_contains($lower, 'not found') && str_contains($lower, 'node') => 'Node tiada pada pelayan.',
            $output === '' => 'Tiada sebab dilaporkan oleh perender.',
            default => str($output)->limit(300)->value(),
        };
    }

    private function disk()
    {
        return Storage::disk(config('dynoads.poster.disk'));
    }
}
