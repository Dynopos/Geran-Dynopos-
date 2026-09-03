<?php

namespace App\Livewire\AdSets;

use App\Exceptions\MetaApiException;
use App\Models\AdSet;
use App\Models\AdVariant;
use App\Services\AdLauncher;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Lancarkan iklan')]
class Run extends Component
{
    public AdSet $adSet;

    public ?string $error = null;

    public ?string $notice = null;

    public function mount(AdSet $adSet): void
    {
        $this->adSet = $adSet->load('variants');
    }

    /** Satu klik pengesahan manusia — peraturan mutlak #6. */
    public function runAll(AdLauncher $launcher)
    {
        $this->reset('error', 'notice');

        try {
            $launcher->runAll($this->adSet);
            $this->notice = 'Semua iklan kini hidup. Duit mula dibelanjakan.';
        } catch (MetaApiException $e) {
            $this->error = $e->forHuman();
        }

        $this->adSet->refresh()->load('variants');

        return null;
    }

    public function pauseVariant(int $variantId, AdLauncher $launcher): void
    {
        $this->reset('error', 'notice');

        $variant = $this->adSet->variants()->findOrFail($variantId);

        try {
            $launcher->pauseOne($variant);
            $this->notice = "Iklan #{$variant->position} dipause.";
        } catch (MetaApiException $e) {
            $this->error = $e->forHuman();
        }

        $this->adSet->refresh()->load('variants');
    }

    public function runVariant(int $variantId, AdLauncher $launcher): void
    {
        $this->reset('error', 'notice');

        $variant = $this->adSet->variants()->findOrFail($variantId);

        try {
            $launcher->runOne($variant);
            $this->notice = "Iklan #{$variant->position} kini hidup.";
        } catch (MetaApiException $e) {
            $this->error = $e->forHuman();
        }

        $this->adSet->refresh()->load('variants');
    }

    public function anyLive(): bool
    {
        return $this->adSet->variants->contains(fn (AdVariant $v) => $v->isLive());
    }

    public function render()
    {
        return view('livewire.ad-sets.run');
    }
}
