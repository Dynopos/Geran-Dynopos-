<?php

namespace App\Services\Caption;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiWriter implements CaptionWriter
{
    public function name(): string
    {
        return 'OpenAI';
    }

    public function keyName(): string
    {
        return 'OPENAI_API_KEY';
    }

    public function configured(): bool
    {
        return filled(config('dynoads.caption.openai.api_key'));
    }

    public function write(string $systemPrompt, string $userPrompt): string
    {
        $config = config('dynoads.caption.openai');

        $response = Http::timeout((int) config('dynoads.caption.timeout'))
            ->withToken((string) $config['api_key'])
            ->post(rtrim((string) $config['base_url'], '/').'/v1/chat/completions', [
                'model' => $config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                // max_completion_tokens, bukan max_tokens: model OpenAI yang
                // lebih baharu menolak nama lama.
                'max_completion_tokens' => (int) config('dynoads.caption.max_tokens'),
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->name().' pulangkan '.$response->status().'.');
        }

        return (string) $response->json('choices.0.message.content', '');
    }
}
