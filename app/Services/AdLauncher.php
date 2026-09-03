<?php

namespace App\Services;

use App\Exceptions\MetaApiException;
use App\Models\AdSet;
use App\Models\AdVariant;
use App\Models\AutoAction;
use App\Models\MetricDaily;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Orkestra antara DB dan Meta.
 *
 * Setiap tindakan yang mengubah Meta direkod dalam auto_actions DAHULU
 * (peraturan mutlak #4), dan setiap objek dibuat PAUSED (peraturan mutlak #3).
 */
class AdLauncher
{
    public function __construct(protected MetaAdsService $meta) {}

    /**
     * Buat campaign + adset + creative + ad untuk setiap variant. Semua PAUSED.
     * Satu gambar = satu campaign, supaya split test membandingkan gambar.
     */
    public function createAll(AdSet $set): AdSet
    {
        foreach ($set->variants as $variant) {
            if ($variant->meta_ad_id) {
                continue; // sudah dibuat — jangan buat dua kali
            }

            $this->createOne($set, $variant);
        }

        $set->update(['status' => 'created']);

        return $set->refresh();
    }

    public function createOne(AdSet $set, AdVariant $variant): AdVariant
    {
        $name = $this->meta->buildName($set->id, $variant->position);

        $action = AutoAction::start(
            action: 'create_campaign',
            reason: "Buat campaign PAUSED untuk iklan #{$variant->position} ({$name}).",
            set: $set,
            variant: $variant,
            payload: ['name' => $name, 'daily_budget_sen' => $set->daily_budget_sen],
        );

        try {
            $hash = $variant->meta_image_hash ?: $this->meta->uploadImage($this->absolutePath($variant->image_path));
            $variant->update(['meta_image_hash' => $hash]);

            $campaignId = $this->meta->createCampaign($name, $set->daily_budget_sen);
            $variant->update(['meta_campaign_id' => $campaignId]);

            $adsetId = $this->meta->createAdSet($campaignId, $name, $set->region_key);
            $variant->update(['meta_adset_id' => $adsetId]);

            $creativeId = $this->meta->createCreative($name, $hash, (string) $variant->caption);
            $variant->update(['meta_creative_id' => $creativeId]);

            $adId = $this->meta->createAd($adsetId, $name, $creativeId);

            $variant->update([
                'meta_ad_id' => $adId,
                'status' => 'paused',
                'last_error' => null,
            ]);

            $action->succeed("Campaign {$campaignId} dibuat, status PAUSED.");
        } catch (MetaApiException $e) {
            $variant->update(['status' => 'failed', 'last_error' => $e->forHuman()]);
            $action->fail($e->forHuman());

            throw $e;
        }

        return $variant->refresh();
    }

    /** Butang RUN — satu-satunya tempat duit mula dibelanjakan. */
    public function runAll(AdSet $set): AdSet
    {
        foreach ($set->variants as $variant) {
            if ($variant->status === 'failed' || ! $variant->meta_ad_id) {
                continue;
            }

            $this->runOne($variant);
        }

        $set->update(['status' => 'running']);

        return $set->refresh();
    }

    public function runOne(AdVariant $variant): AdVariant
    {
        $this->guardOwnership($variant);

        $action = AutoAction::start(
            action: 'run',
            reason: "Peniaga tekan RUN untuk iklan #{$variant->position}.",
            variant: $variant,
            payload: ['campaign_id' => $variant->meta_campaign_id],
        );

        try {
            // Campaign dulu, kemudian adset, kemudian ad — supaya tiada ad hidup
            // di bawah campaign yang masih paused.
            $this->meta->activate($variant->meta_campaign_id);
            $this->meta->activate($variant->meta_adset_id);
            $this->meta->activate($variant->meta_ad_id);

            $variant->update(['status' => 'active', 'last_error' => null]);
            $action->succeed('Campaign, ad set dan ad kini ACTIVE.');
        } catch (MetaApiException $e) {
            $variant->update(['last_error' => $e->forHuman()]);
            $action->fail($e->forHuman());

            throw $e;
        }

        return $variant->refresh();
    }

    public function pauseOne(AdVariant $variant, string $reason = 'Peniaga tekan pause.'): AdVariant
    {
        $this->guardOwnership($variant);

        $action = AutoAction::start(
            action: 'pause',
            reason: $reason,
            variant: $variant,
            payload: ['campaign_id' => $variant->meta_campaign_id],
        );

        try {
            $this->meta->pause($variant->meta_ad_id);
            $this->meta->pause($variant->meta_adset_id);
            $this->meta->pause($variant->meta_campaign_id);

            $variant->update(['status' => 'paused']);
            $action->succeed('Iklan dipause.');
        } catch (MetaApiException $e) {
            $variant->update(['last_error' => $e->forHuman()]);
            $action->fail($e->forHuman());

            throw $e;
        }

        return $variant->refresh();
    }

    /** Tarik insights dan simpan. PAPARAN SAHAJA — tiada keputusan automatik di Fasa 0. */
    public function syncMetrics(AdSet $set): AdSet
    {
        $action = AutoAction::start(
            action: 'sync_metrics',
            reason: 'Refresh angka dari Meta.',
            set: $set,
        );

        $failures = [];

        foreach ($set->variants as $variant) {
            if (! $variant->meta_campaign_id) {
                continue;
            }

            try {
                $insights = $this->meta->getInsights($variant->meta_campaign_id);

                MetricDaily::updateOrCreate(
                    ['ad_variant_id' => $variant->id, 'date' => today()->toDateString()],
                    [
                        'spend_sen' => $insights['spend_sen'],
                        'impressions' => $insights['impressions'],
                        'clicks' => $insights['clicks'],
                        'leads' => $insights['leads'],
                        'ctr' => $insights['ctr'],
                    ]
                );
            } catch (MetaApiException $e) {
                $failures[] = "#{$variant->position}: ".$e->forHuman();
            }
        }

        $failures === []
            ? $action->succeed('Angka dikemas kini.')
            : $action->fail(implode(' | ', $failures));

        return $set->refresh();
    }

    /**
     * Peraturan mutlak #2: app tidak pernah menyentuh campaign yang bukan ia buat.
     */
    protected function guardOwnership(AdVariant $variant): void
    {
        if (! $variant->isOwnedByApp()) {
            throw new RuntimeException(
                'Iklan ini tiada campaign yang dibuat oleh app. App tidak menyentuh campaign lain dalam akaun.'
            );
        }
    }

    protected function absolutePath(string $relativePath): string
    {
        return Storage::disk('public')->path($relativePath);
    }
}
