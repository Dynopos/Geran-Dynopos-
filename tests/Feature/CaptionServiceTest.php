<?php

use App\Services\CaptionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('memparse JSON caption dari balasan Claude', function () {
    Http::fake(['*/v1/messages' => Http::response([
        'content' => [['type' => 'text', 'text' => '["Caption satu.", "Caption dua."]']],
    ])]);

    $captions = app(CaptionService::class)->generate('kira duit lambat', 'sistem POS', 2);

    expect($captions)->toBe(['Caption satu.', 'Caption dua.']);

    Http::assertSent(fn (Request $r) => $r['model'] === config('dynoads.claude.model')
        && str_contains($r['messages'][0]['content'], 'kira duit lambat'));
});

it('mengeluarkan JSON walaupun model bungkus dengan teks lain', function () {
    Http::fake(['*/v1/messages' => Http::response([
        'content' => [['type' => 'text', 'text' => "Ini caption anda:\n```json\n[\"Satu.\"]\n```"]],
    ])]);

    expect(app(CaptionService::class)->generate('masalah', 'tawaran', 1))->toBe(['Satu.']);
});

it('cuba semula bila balasan pertama bukan JSON', function () {
    Http::fakeSequence()
        ->push(['content' => [['type' => 'text', 'text' => 'maaf saya tak faham']]])
        ->push(['content' => [['type' => 'text', 'text' => '["Caption baik."]']]]);

    expect(app(CaptionService::class)->generate('masalah', 'tawaran', 1))->toBe(['Caption baik.']);

    Http::assertSentCount(2);
});

it('jatuh ke caption asas bila Claude gagal — peniaga tidak tersekat', function () {
    Http::fake(['*/v1/messages' => Http::response([], 500)]);

    $captions = app(CaptionService::class)->generate('kira duit lambat', 'sistem POS fullset', 2);

    expect($captions)->toHaveCount(2)
        ->and($captions[0])->toContain('Tekan WhatsApp untuk info lanjut');
});

it('membuang perkataan larangan dari caption', function () {
    Http::fake(['*/v1/messages' => Http::response([
        'content' => [['type' => 'text', 'text' => '["Sistem POS terbaik di Malaysia, dijamin untung."]']],
    ])]);

    $caption = app(CaptionService::class)->generate('masalah', 'tawaran', 1)[0];

    expect(mb_strtolower($caption))->not->toContain('terbaik di malaysia')
        ->and(mb_strtolower($caption))->not->toContain('dijamin');
});

it('mengesan perkataan larangan dalam caption yang diedit sendiri', function () {
    $service = app(CaptionService::class);

    expect($service->hasForbiddenWord('Hasil dijamin dalam 7 hari'))->toBeTrue()
        ->and($service->hasForbiddenWord('Tekan WhatsApp untuk info lanjut'))->toBeFalse()
        ->and($service->hasForbiddenWord('Nak tengok demo?'))->toBeTrue();
});
