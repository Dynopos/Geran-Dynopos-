<?php

namespace Database\Factories;

use App\Models\AdSet;
use App\Models\AdVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdVariant> */
class AdVariantFactory extends Factory
{
    protected $model = AdVariant::class;

    public function definition(): array
    {
        return [
            'ad_set_id' => AdSet::factory(),
            'position' => 1,
            'image_path' => 'dynoads/1/1.jpg',
            'caption' => "Kira duit lambat?\n\nTekan WhatsApp untuk info lanjut.",
            'status' => 'draft',
        ];
    }

    public function created(): static
    {
        return $this->state(fn () => [
            'meta_image_hash' => 'hash123',
            'meta_campaign_id' => '120200000000001',
            'meta_adset_id' => '120200000000002',
            'meta_creative_id' => '120200000000003',
            'meta_ad_id' => '120200000000004',
            'status' => 'paused',
        ]);
    }
}
