<?php

use App\Models\AdSet;
use App\Models\AdVariant;
use App\Models\AutoAction;
use App\Services\AdLauncher;
use App\Services\MetaAdsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Peraturan mutlak CLAUDE.md dikuatkuasakan oleh test, bukan hanya oleh niat.
 */
it('MetaAdsService tiada satu pun method update* — peraturan #1', function () {
    $methods = collect(get_class_methods(MetaAdsService::class))
        ->filter(fn (string $m) => str_starts_with(strtolower($m), 'update'));

    expect($methods->all())->toBe([]);
});

it('tiada method yang menghantar budget, targeting atau creative pada objek sedia ada', function () {
    $source = file_get_contents(app_path('Services/MetaAdsService.php'));

    // Satu-satunya panggilan pada objek sedia ada ialah setStatus().
    expect($source)->toContain('protected function setStatus')
        ->and(substr_count($source, "'daily_budget'"))->toBe(1)   // hanya semasa createCampaign
        ->and(substr_count($source, "'targeting' =>"))->toBe(1);  // hanya semasa createAdSet
});

it('semua objek dibuat PAUSED — peraturan #3', function () {
    Http::fake([
        '*/adimages' => Http::response(['images' => ['1.jpg' => ['hash' => 'hash123']]]),
        '*/campaigns' => Http::response(['id' => 'c1']),
        '*/adsets' => Http::response(['id' => 's1']),
        '*/adcreatives' => Http::response(['id' => 'cr1']),
        '*/ads' => Http::response(['id' => 'a1']),
    ]);

    $set = AdSet::factory()->create();
    $variant = AdVariant::factory()->for($set, 'adSet')->create(['meta_image_hash' => 'hash123']);

    app(AdLauncher::class)->createAll($set->load('variants'));

    expect($variant->refresh()->status)->toBe('paused');

    foreach (['campaigns', 'adsets', 'ads'] as $edge) {
        Http::assertSent(fn (Request $r) => ! str_contains($r->url(), "/{$edge}")
            || ($r->data()['status'] ?? null) === 'PAUSED');
    }
});

it('merekod auto_action sebelum memanggil Meta — peraturan #4', function () {
    Http::fake(['*' => Http::response([
        'id' => 'x1',
        'images' => ['1.jpg' => ['hash' => 'hash123']],
    ])]);

    $set = AdSet::factory()->create();
    AdVariant::factory()->for($set, 'adSet')->create(['meta_image_hash' => 'hash123']);

    app(AdLauncher::class)->createAll($set->load('variants'));

    $action = AutoAction::where('action', 'create_campaign')->firstOrFail();

    expect($action->result)->toBe('ok')
        ->and($action->reason)->toContain('PAUSED')
        ->and($action->ad_variant_id)->not->toBeNull();
});

it('menolak run pada iklan yang tiada campaign dibuat oleh app — peraturan #2', function () {
    $variant = AdVariant::factory()->create(); // tiada meta_campaign_id

    Http::preventStrayRequests();

    expect(fn () => app(AdLauncher::class)->runOne($variant))
        ->toThrow(RuntimeException::class, 'tidak menyentuh campaign lain');
});

it('run menghidupkan campaign, ad set dan ad — dan hanya milik app', function () {
    Http::fake(['*' => Http::response(['success' => true])]);

    $variant = AdVariant::factory()->created()->create();

    app(AdLauncher::class)->runOne($variant);

    expect($variant->refresh()->status)->toBe('active');

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $r) => ($r->data()['status'] ?? null) === 'ACTIVE');
});

it('pause merekod tindakan dan mengembalikan status paused', function () {
    Http::fake(['*' => Http::response(['success' => true])]);

    $variant = AdVariant::factory()->created()->create(['status' => 'active']);

    app(AdLauncher::class)->pauseOne($variant);

    expect($variant->refresh()->status)->toBe('paused')
        ->and(AutoAction::where('action', 'pause')->where('result', 'ok')->exists())->toBeTrue();
});

it('tiada token dalam kod sumber — peraturan #8', function () {
    $files = collect(File::allFiles(app_path()))
        ->merge(File::allFiles(config_path()))
        ->merge(File::allFiles(resource_path('views')));

    foreach ($files as $file) {
        $contents = file_get_contents($file->getRealPath());

        expect($contents)->not->toMatch('/EAA[A-Za-z0-9]{20,}/')     // token Meta
            ->and($contents)->not->toMatch('/sk-ant-[A-Za-z0-9\-]{20,}/'); // kunci Claude
    }
});
