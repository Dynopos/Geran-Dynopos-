<?php

namespace App\Livewire\AdSets;

use App\Models\AdSet;
use App\Services\AdLauncher;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Prestasi iklan')]
class Dashboard extends Component
{
    public AdSet $adSet;

    public ?string $notice = null;

    public function mount(AdSet $adSet): void
    {
        $this->adSet = $adSet;
    }

    public function refreshMetrics(AdLauncher $launcher): void
    {
        $launcher->syncMetrics($this->adSet);
        $this->adSet->refresh();
        $this->notice = 'Angka ditarik semula dari Meta.';
    }

    public function render()
    {
        $variants = $this->adSet->variants()->with('metrics')->get();

        return view('livewire.ad-sets.dashboard', [
            'variants' => $variants,
            'totalSpendSen' => $variants->sum(fn ($v) => $v->spendSen()),
            'totalLeads' => $variants->sum(fn ($v) => $v->leads()),
            'actions' => $this->adSet->autoActions()->limit(30)->get(),
        ]);
    }
}
