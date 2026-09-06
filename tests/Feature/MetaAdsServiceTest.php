<?php

use App\Exceptions\MetaApiException;
use App\Services\MetaAdsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('menghantar payload campaign CBO yang betul dan sentiasa PAUSED', function () {
    Http::fake(['*/campaigns' => Http::response(['id' => '120100000000001'])]);

    $id = app(MetaAdsService::class)->createCampaign('DYNOADS-1-1-03SEP26', 3700);

    expect($id)->toBe('120100000000001');

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return str_contains($request->url(), '/v21.0/act_701202512896650/campaigns')
            && $body['objective'] === 'OUTCOME_ENGAGEMENT'
            && $body['status'] === 'PAUSED'
            && $body['bid_strategy'] === 'LOWEST_COST_WITHOUT_CAP'
            && (int) $body['daily_budget'] === 3700
            && $body['special_ad_categories'] === '[]';
    });
});

it('menghantar ad set CONVERSATIONS ke WhatsApp dengan promoted_object', function () {
    Http::fake(['*/adsets' => Http::response(['id' => '120100000000002'])]);

    app(MetaAdsService::class)->createAdSet('120100000000001', 'DYNOADS-1-1-03SEP26');

    Http::assertSent(function (Request $request) {
        $body = $request->data();
        $promoted = json_decode($body['promoted_object'], true);

        return $body['optimization_goal'] === 'CONVERSATIONS'
            && $body['destination_type'] === 'WHATSAPP'
            && $body['billing_event'] === 'IMPRESSIONS'
            && $body['status'] === 'PAUSED'
            && $promoted['page_id'] === '377330642350146'
            && $promoted['whatsapp_phone_number'] === '60187922844';
    });
});

it('seluruh Malaysia mengecualikan Sarawak, Labuan dan Sabah', function () {
    $targeting = app(MetaAdsService::class)->buildTargeting(null);

    expect($targeting['geo_locations']['countries'])->toBe(['MY'])
        ->and(collect($targeting['geo_locations']['excluded_geo_locations']['regions'])->pluck('key')->all())
        ->toBe(['2546', '2550', '2551'])
        ->and($targeting['age_min'])->toBe(25)
        ->and($targeting['age_max'])->toBe(65)
        ->and($targeting['locales'])->toBe([41])
        ->and($targeting['targeting_automation']['advantage_audience'])->toBe(1)
        ->and($targeting['geo_locations']['location_types'])->toBe(['home', 'recent'])
        ->and($targeting)->not->toHaveKey('individual_setting');
});

it('satu negeri guna geo_locations.regions dan tiada pengecualian', function () {
    $targeting = app(MetaAdsService::class)->buildTargeting('3847');

    expect($targeting['geo_locations']['regions'])->toBe([['key' => '3847']])
        ->and($targeting['geo_locations'])->not->toHaveKey('countries')
        ->and($targeting['targeting_automation']['individual_setting'])->toBe(['geo_locations' => 1]);
});

it('boleh matikan targeting_automation.individual_setting tanpa kesan lain', function () {
    config()->set('dynoads.targeting.send_geo_targeting_automation', false);

    $targeting = app(MetaAdsService::class)->buildTargeting('3847');

    expect($targeting['targeting_automation'])->toBe(['advantage_audience' => 1])
        ->and($targeting['geo_locations']['regions'])->toBe([['key' => '3847']]);
});

it('creative guna butang WHATSAPP_MESSAGE dan link api.whatsapp.com', function () {
    Http::fake(['*/adcreatives' => Http::response(['id' => '120100000000003'])]);

    app(MetaAdsService::class)->createCreative('DYNOADS-1-1-03SEP26', 'hash123', 'Tekan WhatsApp untuk info lanjut');

    Http::assertSent(function (Request $request) {
        $spec = json_decode($request->data()['object_story_spec'], true);

        return $spec['page_id'] === '377330642350146'
            && str_starts_with($spec['link_data']['link'], 'https://api.whatsapp.com/send')
            && $spec['link_data']['image_hash'] === 'hash123'
            && $spec['link_data']['call_to_action']['type'] === 'WHATSAPP_MESSAGE';
    });
});

it('link WhatsApp membawa ayat pra-isi Bahasa Melayu', function () {
    Http::fake(['*/adcreatives' => Http::response(['id' => 'cr1'])]);

    app(MetaAdsService::class)->createCreative('DYNOADS-1-1-03SEP26', 'hash123', 'caption');

    Http::assertSent(function (Request $request) {
        $link = json_decode($request->data()['object_story_spec'], true)['link_data']['link'];
        parse_str(parse_url($link, PHP_URL_QUERY) ?? '', $query);

        // Tanpa ini Meta isi ayat defaultnya sendiri dalam Bahasa Inggeris.
        return str_starts_with($link, 'https://api.whatsapp.com/send')
            && ($query['text'] ?? '') === 'Hi, saya nak tahu lanjut pasal ni.';
    });
});

it('link kekal bersih bila ayat pra-isi dikosongkan', function () {
    config()->set('dynoads.ad.whatsapp_prefill', '');
    Http::fake(['*/adcreatives' => Http::response(['id' => 'cr1'])]);

    app(MetaAdsService::class)->createCreative('DYNOADS-1-1-03SEP26', 'hash123', 'caption');

    Http::assertSent(fn (Request $request) => json_decode($request->data()['object_story_spec'], true)['link_data']['link']
        === 'https://api.whatsapp.com/send');
});

it('ad dibuat PAUSED dan merujuk creative_id', function () {
    Http::fake(['*/ads' => Http::response(['id' => '120100000000004'])]);

    app(MetaAdsService::class)->createAd('120100000000002', 'DYNOADS-1-1-03SEP26', '120100000000003');

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $body['status'] === 'PAUSED'
            && json_decode($body['creative'], true) === ['creative_id' => '120100000000003'];
    });
});

it('activate hanya menghantar medan status, tiada budget atau targeting', function () {
    Http::fake(['*' => Http::response(['success' => true])]);

    app(MetaAdsService::class)->activate('120100000000001');

    Http::assertSent(fn (Request $request) => array_keys($request->data()) === ['status', 'access_token']
        && $request->data()['status'] === 'ACTIVE');
});

it('mengira lead WhatsApp dari onsite_conversion.total_messaging_connection', function () {
    Http::fake(['*/insights*' => Http::response([
        'data' => [[
            'spend' => '41.55',
            'impressions' => '5321',
            'clicks' => '88',
            'ctr' => '1.654',
            'actions' => [
                ['action_type' => 'link_click', 'value' => '88'],
                ['action_type' => 'onsite_conversion.total_messaging_connection', 'value' => '4'],
                ['action_type' => 'post_engagement', 'value' => '120'],
            ],
        ]],
    ])]);

    $insights = app(MetaAdsService::class)->getInsights('120100000000001');

    expect($insights['spend_sen'])->toBe(4155)
        ->and($insights['leads'])->toBe(4)
        ->and($insights['clicks'])->toBe(88)
        ->and($insights['impressions'])->toBe(5321)
        ->and($insights['ctr'])->toBe(1.654);
});

it('mengutamakan error_user_msg bila Meta tolak dan tidak membocorkan token', function () {
    Http::fake(['*/adsets' => Http::response([
        'error' => [
            'message' => 'Invalid parameter',
            'code' => 100,
            'error_subcode' => 1885183,
            'error_user_msg' => 'Nombor WhatsApp tidak sah untuk Page ini.',
        ],
    ], 400)]);

    try {
        app(MetaAdsService::class)->createAdSet('120100000000001', 'DYNOADS-1-1-03SEP26');
        $this->fail('sepatutnya melontar MetaApiException');
    } catch (MetaApiException $e) {
        expect($e->forHuman())->toBe('Nombor WhatsApp tidak sah untuk Page ini.')
            ->and($e->errorCode)->toBe(100)
            ->and($e->errorSubcode)->toBe(1885183)
            ->and($e->getMessage())->not->toContain(config('dynoads.meta.token'));
    }
});

it('menamakan objek ikut format DYNOADS-{set}-{n}-{DDMMMYY}', function () {
    Carbon\Carbon::setTestNow('2026-09-03');

    expect(app(MetaAdsService::class)->buildName(7, 2))->toBe('DYNOADS-7-2-03SEP26');

    Carbon\Carbon::setTestNow();
});

it('menambah prefix act_ pada ad account id yang tertinggal', function () {
    config()->set('dynoads.meta.ad_account_id', '701202512896650');
    Http::fake(['*/campaigns' => Http::response(['id' => '1'])]);

    (new MetaAdsService)->createCampaign('DYNOADS-1-1-03SEP26', 3700);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'act_701202512896650/campaigns'));
});
