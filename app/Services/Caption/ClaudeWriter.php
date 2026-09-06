<?php

namespace App\Services\Caption;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeWriter implements CaptionWriter
{
    public function name(): string
    {
        return 'Claude';
    }

    public function keyName(): string
    {
        return 'ANTHROPIC_API_KEY';
    }

    public function configured(): bool
    {
        return filled(config('dynoads.caption.claude.api_key'));
    }

    public function write(string $systemPrompt, string $userPrompt): string
    {
        $config = config('dynoads.caption.claude');

        $response = Http::timeout((int) config('dynoads.caption.timeout'))
            ->withHeaders([
                'x-api-key' => (string) $config['api_key'],
                'anthropic-version' => (string) $config['version'],
            ])
            ->post(rtrim((string) $config['base_url'], '/').'/v1/messages', [
                'model' => $config['model'],
                'max_tokens' => (int) config('dynoads.caption.max_tokens'),
                'system' => $systemPrompt,
                'messages' => [['role' => 'user', 'content' => $userPrompt]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->name().' pulangkan '.$response->status().'.');
        }

        return (string) collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");
    }
}
