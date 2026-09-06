<?php

namespace App\Livewire\AdSets;

use App\Exceptions\MetaApiException;
use App\Models\AdSet;
use App\Services\AdLauncher;
use App\Services\CaptionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Semak sebelum lancar')]
class Review extends Component
{
    public AdSet $adSet;

    /** @var array<int, string> caption ikut id variant */
    public array $captions = [];

    public bool $working = false;

    public ?string $error = null;

    /** Diisi bila caption datang dari acuan asas, bukan dari Claude. */
    public ?string $fallbackReason = null;

    public function mount(AdSet $adSet, CaptionService $captions): void
    {
        $this->adSet = $adSet->load('variants');

        $missing = $this->adSet->variants->whereNull('caption');

        if ($missing->isNotEmpty()) {
            $set = $captions->generate(
                $this->adSet->problem,
                $this->adSet->offer,
                $missing->count()
            );

            $this->fallbackReason = $set->fromFallback ? $set->reason : null;

            foreach ($missing->values() as $i => $variant) {
                $variant->update(['caption' => $set->captions[$i] ?? $set->captions[0]]);
            }

            $this->adSet->load('variants');
        }

        foreach ($this->adSet->variants as $variant) {
            $this->captions[$variant->id] = (string) $variant->caption;
        }
    }

    public function regenerate(CaptionService $captions): void
    {
        $set = $captions->generate(
            $this->adSet->problem,
            $this->adSet->offer,
            $this->adSet->variants->count()
        );

        $this->fallbackReason = $set->fromFallback ? $set->reason : null;

        foreach ($this->adSet->variants->values() as $i => $variant) {
            $variant->update(['caption' => $set->captions[$i] ?? $set->captions[0]]);
            $this->captions[$variant->id] = (string) $variant->caption;
        }

        $this->adSet->load('variants');
    }

    /** Approve → campaign dibuat di Meta, semuanya PAUSED. Tiada duit lagi. */
    public function approve(AdLauncher $launcher, CaptionService $captions)
    {
        $this->error = null;
        $this->working = true;

        foreach ($this->adSet->variants as $variant) {
            $caption = trim((string) ($this->captions[$variant->id] ?? ''));

            if ($caption === '') {
                $this->working = false;
                $this->error = 'Setiap iklan kena ada caption.';

                return null;
            }

            if ($captions->hasForbiddenWord($caption)) {
                $this->working = false;
                $this->error = 'Ada perkataan yang Meta biasa tolak dalam caption. Ubah dulu.';

                return null;
            }

            $variant->update(['caption' => $caption]);
        }

        try {
            $launcher->createAll($this->adSet->refresh());
        } catch (MetaApiException $e) {
            $this->working = false;
            $this->error = $e->forHuman();

            return null;
        } finally {
            $this->adSet->refresh()->load('variants');
        }

        $this->working = false;

        return $this->redirectRoute('ad-sets.run', ['adSet' => $this->adSet], navigate: true);
    }

    /** @return array<int, array<int, string>> dakwaan berisiko ikut id variant */
    public function riskyClaims(CaptionService $captions): array
    {
        return collect($this->captions)
            ->map(fn (string $caption) => $captions->riskyClaims($caption))
            ->filter()
            ->all();
    }

    public function render(CaptionService $captions)
    {
        return view('livewire.ad-sets.review', [
            'risiko' => $this->riskyClaims($captions),
        ]);
    }
}
