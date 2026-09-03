<?php

/*
|--------------------------------------------------------------------------
| Setting Meta yang terbukti
|--------------------------------------------------------------------------
|
| Nilai di bawah datang dari 150+ campaign sebenar DynoPOS (kos/lead ~RM10–15).
| Ia BUKAN tekaan. Jangan ubah tanpa data campaign baharu yang menyokongnya.
|
*/

return [

    'meta' => [
        'token' => env('META_ACCESS_TOKEN'),
        'ad_account_id' => env('META_AD_ACCOUNT_ID'),
        'page_id' => env('META_PAGE_ID'),
        'wa_phone' => env('META_WA_PHONE'),
        'api_version' => env('META_API_VERSION', 'v21.0'),
        'graph_url' => env('META_GRAPH_URL', 'https://graph.facebook.com'),
        'timeout' => 60,
    ],

    // Satu gambar = satu campaign. Itu asas split test.
    'campaign' => [
        'objective' => 'OUTCOME_ENGAGEMENT',
        'bid_strategy' => 'LOWEST_COST_WITHOUT_CAP',
        'special_ad_categories' => [],
        'buying_type' => 'AUCTION',
    ],

    'adset' => [
        'optimization_goal' => 'CONVERSATIONS',
        'destination_type' => 'WHATSAPP',
        'billing_event' => 'IMPRESSIONS',
    ],

    'targeting' => [
        'age_min' => 25,
        'age_max' => 65,
        'locales' => [41],                       // 41 = Bahasa Melayu
        'advantage_audience' => 1,
        'location_types' => ['home', 'recent'],
        'brand_safety_content_filter_levels' => ['FACEBOOK_RELAXED', 'AN_RELAXED'],
        'country' => 'MY',

        // Seluruh Malaysia = countries[MY] tolak tiga region ini.
        'excluded_regions' => [
            2546 => 'Sarawak',
            2550 => 'Labuan',
            2551 => 'Sabah',
        ],

        // Bila user pilih satu negeri, kod hantar field ini (ikut setup manual yang
        // terbukti). Kalau Meta pulangkan ralat parameter, tukar kepada false —
        // tiada kesan lain. Lihat README.
        'send_geo_targeting_automation' => env('DYNOADS_GEO_TARGETING_AUTOMATION', true),
    ],

    'ad' => [
        'call_to_action_type' => 'WHATSAPP_MESSAGE',
        'link' => 'https://api.whatsapp.com/send',
    ],

    'budget' => [
        'default_daily_sen' => 3700,   // RM37/hari
        'min_daily_sen' => 1000,       // RM10/hari
        'max_daily_sen' => 20000,      // RM200/hari
    ],

    'creative' => [
        'max_images' => 4,
        'min_images' => 1,
        'size' => 1080,
    ],

    // DYNOADS-{setID}-{n}-{DDMMMYY}
    'name_prefix' => 'DYNOADS',

    'metrics' => [
        // Lead WhatsApp dikira dari action_type ini.
        'lead_action_types' => [
            'onsite_conversion.total_messaging_connection',
        ],
    ],

    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => '2023-06-01',
        'max_tokens' => 1024,
        'timeout' => 60,
        'retries' => 2,

        // Caption tak boleh menjanjikan hasil atau guna ayat larangan Meta.
        'forbidden_words' => [
            'dijamin', 'jaminan', 'pasti untung', 'guaranteed',
            'terbaik di malaysia', 'nombor 1', 'no.1', 'no 1',
            'demo', 'percuma 100%', 'tanpa risiko',
        ],
    ],
];
