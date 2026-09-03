<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Caption iklan. Semua panggilan Claude API lalu di sini —
 * tidak pernah terus dari Livewire (peraturan CLAUDE.md).
 *
 * LLM hanya menulis teks. Ia tidak pernah memanggil Meta (peraturan mutlak #5).
 */
class CaptionService
{
    /**
     * Jana beberapa caption berbeza — satu untuk setiap gambar,
     * supaya split test membandingkan sudut ayat, bukan hanya gambar.
     *
     * @return array<int, string>
     */
    public function generate(string $problem, string $offer, int $count = 1): array
    {
        $count = max(1, min($count, (int) config('dynoads.creative.max_images')));
        $attempts = (int) config('dynoads.claude.retries') + 1;
        $last = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $captions = $this->ask($problem, $offer, $count);

                if (count($captions) >= $count) {
                    return array_slice($captions, 0, $count);
                }

                $last = new RuntimeException('Claude pulangkan caption tidak cukup.');
            } catch (RuntimeException $e) {
                $last = $e;
            }
        }

        // Jangan halang peniaga sebab AI gagal — bagi caption asas yang boleh diedit.
        return $this->fallback($problem, $offer, $count);
    }

    /** @return array<int, string> */
    protected function ask(string $problem, string $offer, int $count): array
    {
        $response = Http::timeout((int) config('dynoads.claude.timeout'))
            ->withHeaders([
                'x-api-key' => (string) config('dynoads.claude.api_key'),
                'anthropic-version' => (string) config('dynoads.claude.version'),
            ])
            ->post(rtrim((string) config('dynoads.claude.base_url'), '/').'/v1/messages', [
                'model' => config('dynoads.claude.model'),
                'max_tokens' => (int) config('dynoads.claude.max_tokens'),
                'system' => $this->systemPrompt(),
                'messages' => [[
                    'role' => 'user',
                    'content' => $this->userPrompt($problem, $offer, $count),
                ]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Claude API gagal: '.$response->status());
        }

        $text = (string) collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return $this->parse($text);
    }

    /** @return array<int, string> */
    protected function parse(string $text): array
    {
        // Model kadang bungkus JSON dalam fence. Ambil array pertama yang sah.
        if (preg_match('/\[[\s\S]*\]/', $text, $m)) {
            $text = $m[0];
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Balasan Claude bukan JSON yang sah.');
        }

        return collect($decoded)
            ->map(fn ($c) => is_array($c) ? (string) ($c['caption'] ?? '') : (string) $c)
            ->map(fn (string $c) => $this->clean($c))
            ->filter()
            ->values()
            ->all();
    }

    /** Buang ayat larangan Meta dan ruang berlebihan. */
    public function clean(string $caption): string
    {
        $caption = trim(preg_replace('/[ \t]+/', ' ', $caption));

        foreach ((array) config('dynoads.claude.forbidden_words') as $word) {
            $caption = preg_replace('/'.preg_quote($word, '/').'/iu', '', $caption);
        }

        return trim(preg_replace('/ +([,.!?])/', '$1', preg_replace('/[ \t]{2,}/', ' ', $caption)));
    }

    public function hasForbiddenWord(string $caption): bool
    {
        foreach ((array) config('dynoads.claude.forbidden_words') as $word) {
            if (mb_stripos($caption, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function systemPrompt(): string
    {
        return <<<'TXT'
        Kau AI Nurin, penulis copy untuk peniaga kecil Malaysia.

        Tulis caption iklan Facebook Click-to-WhatsApp dalam Bahasa Melayu santai
        (bahasa pasar yang sopan, bukan BM baku sekolah). Setiap caption:
        - 2 hingga 4 baris pendek, mudah dibaca atas telefon
        - mula dengan masalah pelanggan, tutup dengan jalan keluar yang tenang
        - jangan janji hasil, jangan guna perkataan "dijamin" atau "terbaik di Malaysia"
        - jangan guna perkataan "demo"
        - akhiri dengan ajakan: Tekan WhatsApp untuk info lanjut

        Balas JSON sahaja: ["caption satu", "caption dua"]. Tiada teks lain.
        TXT;
    }

    protected function userPrompt(string $problem, string $offer, int $count): string
    {
        return "Masalah pelanggan: {$problem}\nTawaran: {$offer}\n\nBagi {$count} caption berbeza sudut.";
    }

    /** @return array<int, string> */
    protected function fallback(string $problem, string $offer, int $count): array
    {
        $base = [
            "{$problem}?\n\n{$offer}\n\nTekan WhatsApp untuk info lanjut.",
            "Ramai peniaga hadap hal sama: {$problem}.\n\n{$offer}\n\nTekan WhatsApp untuk info lanjut.",
            "Kalau {$problem} makan masa anda setiap hari — {$offer}.\n\nTekan WhatsApp untuk info lanjut.",
            "{$offer}\n\nSesuai kalau anda masih bergelut dengan {$problem}.\n\nTekan WhatsApp untuk info lanjut.",
        ];

        return array_slice($base, 0, $count);
    }
}
