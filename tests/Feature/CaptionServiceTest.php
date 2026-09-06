<?php

use App\Services\CaptionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('memparse JSON caption dari balasan Claude', function () {
    Http::fake(['*/v1/messages' => Http::response([
        'content' => [['type' => 'text', 'text' => '["Caption satu.", "Caption dua."]']],
    ])]);

    $set = app(CaptionService::class)->generate('kira duit lambat', 'sistem POS', 2);

    expect($set->captions)->toBe(['Caption satu.', 'Caption dua.'])
        ->and($set->fromFallback)->toBeFalse();

    Http::assertSent(fn (Request $r) => $r['model'] === config('dynoads.claude.model')
        && str_contains($r['messages'][0]['content'], 'kira duit lambat'));
});

it('mengeluarkan JSON walaupun model bungkus dengan teks lain', function () {
    Http::fake(['*/v1/messages' => Http::response([
        'content' => [['type' => 'text', 'text' => "Ini caption anda:\n```json\n[\"Satu.\"]\n```"]],
    ])]);

    expect(app(CaptionService::class)->generate('masalah', 'tawaran', 1)->captions)->toBe(['Satu.']);
});

it('cuba semula bila balasan pertama bukan JSON', function () {
    Http::fakeSequence()
        ->push(['content' => [['type' => 'text', 'text' => 'maaf saya tak faham']]])
        ->push(['content' => [['type' => 'text', 'text' => '["Caption baik."]']]]);

    expect(app(CaptionService::class)->generate('masalah', 'tawaran', 1)->captions)->toBe(['Caption baik.']);

    Http::assertSentCount(2);
});

it('jatuh ke caption asas bila Claude gagal — peniaga tidak tersekat', function () {
    Http::fake(['*/v1/messages' => Http::response([], 500)]);

    $set = app(CaptionService::class)->generate('kira duit lambat', 'sistem POS fullset', 2);

    // Fallback mesti mengaku dirinya — kunci API yang tidak diisi tidak boleh
    // kelihatan sama seperti AI yang menulis dengan teruk.
    expect($set->captions)->toHaveCount(2)
        ->and($set->fromFallback)->toBeTrue()
        ->and($set->reason)->not->toBeEmpty()
        ->and($set->captions[0])->toContain('Tekan WhatsApp untuk info lanjut');
});

it('membuang perkataan larangan dari caption', function () {
    Http::fake(['*/v1/messages' => Http::response([
        'content' => [['type' => 'text', 'text' => '["Sistem POS terbaik di Malaysia, dijamin untung."]']],
    ])]);

    $caption = app(CaptionService::class)->generate('masalah', 'tawaran', 1)->captions[0];

    expect(mb_strtolower($caption))->not->toContain('terbaik di malaysia')
        ->and(mb_strtolower($caption))->not->toContain('dijamin');
});

it('mengesan perkataan larangan dalam caption yang diedit sendiri', function () {
    $service = app(CaptionService::class);

    expect($service->hasForbiddenWord('Hasil dijamin dalam 7 hari'))->toBeTrue()
        ->and($service->hasForbiddenWord('Tekan WhatsApp untuk info lanjut'))->toBeFalse()
        ->and($service->hasForbiddenWord('Nak tengok demo?'))->toBeTrue();
});

it('menyebut kunci API yang tidak diisi sebagai sebab fallback', function () {
    config()->set('dynoads.claude.api_key', '');
    Http::fake(['*/v1/messages' => Http::response([], 401)]);

    $set = app(CaptionService::class)->generate('masalah', 'tawaran', 1);

    expect($set->fromFallback)->toBeTrue()
        ->and($set->reason)->toContain('ANTHROPIC_API_KEY');
});

it('membezakan Claude yang gagal daripada kunci yang tiada', function () {
    config()->set('dynoads.claude.api_key', 'kunci-ujian');
    Http::fake(['*/v1/messages' => Http::response([], 500)]);

    $set = app(CaptionService::class)->generate('masalah', 'tawaran', 1);

    expect($set->reason)->toContain('Claude')
        ->and($set->reason)->not->toContain('ANTHROPIC_API_KEY');
});

it('menandakan dakwaan superlatif yang Meta biasa tolak', function () {
    $service = app(CaptionService::class);

    expect($service->riskyClaims('POS system termurah di Malaysia'))->toBe(['termurah'])
        ->and($service->riskyClaims('Paling murah dan no.1 di pasaran'))
        ->toBe(['paling murah', 'nombor satu'])
        ->and($service->riskyClaims('Full set RM1350 tanpa bayaran bulanan'))->toBe([]);
});

it('dakwaan berisiko hanya diberi amaran, bukan menghalang', function () {
    // Dakwaan itu mungkin memang benar untuk peniaga tu. Kita beritahu risikonya
    // dan biarkan mereka putuskan — bukan menyekat pelancaran.
    expect(app(CaptionService::class)->hasForbiddenWord('POS termurah di Malaysia'))->toBeFalse();
});
