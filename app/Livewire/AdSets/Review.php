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

    public function mount(AdSet $adSet, CaptionService $captions): void
    {
        $this->adSet = $adSet->load('variants');

        $missing = $this->adSet->variants->whereNull('caption');

        if ($missing->isNotEmpty()) {
            $generated = $captions->generate(
                $this->adSet->problem,
                $this->adSet->offer,
                $missing->count()
            );

            foreach ($missing->values() as $i => $variant) {
                $variant->update(['caption' => $generated[$i] ?? $generated[0]]);
            }

            $this->adSet->load('variants');
        }

        foreach ($this->adSet->variants as $variant) {
            $this->captions[$variant->id] = (string) $variant->caption;
        }
    }

    public function regenerate(CaptionService $captions): void
    {
        $generated = $captions->generate(
            $this->adSet->problem,
            $this->adSet->offer,
            $this->adSet->variants->count()
        );

        foreach ($this->adSet->variants->values() as $i => $variant) {
            $variant->update(['caption' => $generated[$i] ?? $generated[0]]);
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

    public function render()
    {
        return view('livewire.ad-sets.review');
    }
}
