<?php

namespace App\Livewire\AdSets;

use App\Models\AdSet;
use App\Models\AdVariant;
use App\Services\ImageProcessor;
use App\Services\MetaAdsService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Buat iklan baru')]
class Create extends Component
{
    use WithFileUploads;

    /** @var array<int, TemporaryUploadedFile> */
    public array $images = [];

    public string $problem = '';

    public string $offer = '';

    public string $phone = '';

    public ?string $regionKey = null;

    public int $budgetRm = 37;

    /** @var array<int, array{key:string,name:string}> */
    public array $regions = [];

    public function mount(MetaAdsService $meta): void
    {
        $this->phone = (string) config('dynoads.meta.wa_phone');
        $this->budgetRm = intdiv((int) config('dynoads.budget.default_daily_sen'), 100);

        try {
            $this->regions = $meta->searchRegions();
        } catch (\Throwable) {
            // Tiada token atau Meta tak dapat dihubungi — seluruh Malaysia masih boleh.
            $this->regions = [];
        }
    }

    protected function rules(): array
    {
        $min = intdiv((int) config('dynoads.budget.min_daily_sen'), 100);
        $max = intdiv((int) config('dynoads.budget.max_daily_sen'), 100);

        return [
            'images' => 'required|array|min:'.config('dynoads.creative.min_images').'|max:'.config('dynoads.creative.max_images'),
            'images.*' => 'image|max:8192',
            'problem' => 'required|string|min:5|max:200',
            'offer' => 'required|string|min:5|max:200',
            'phone' => ['required', 'regex:/^60\d{8,11}$/'],
            'regionKey' => 'nullable|string',
            'budgetRm' => "required|integer|min:{$min}|max:{$max}",
        ];
    }

    protected function messages(): array
    {
        return [
            'images.required' => 'Muat naik sekurang-kurangnya satu gambar.',
            'images.max' => 'Maksimum 4 gambar. Lebih dari tu susah nak baca hasilnya.',
            'images.*.image' => 'Fail kena gambar (JPG, PNG atau WEBP).',
            'problem.required' => 'Tulis masalah pelanggan anda.',
            'offer.required' => 'Tulis apa yang anda tawarkan.',
            'phone.regex' => 'Nombor WhatsApp kena format 60XXXXXXXXX, tiada tanda +.',
        ];
    }

    public function save(ImageProcessor $processor)
    {
        $this->validate();

        $set = AdSet::create([
            'name' => str($this->offer)->limit(60)->value(),
            'problem' => $this->problem,
            'offer' => $this->offer,
            'phone' => $this->phone,
            'region_key' => $this->regionKey ?: null,
            'region_name' => $this->regionName(),
            'daily_budget_sen' => $this->budgetRm * 100,
            'status' => 'draft',
        ]);

        foreach (array_values($this->images) as $index => $upload) {
            $relative = "dynoads/{$set->id}/".($index + 1).'.jpg';

            Storage::disk('public')->makeDirectory(dirname($relative));

            $processor->squareCrop(
                $upload->getRealPath(),
                Storage::disk('public')->path($relative)
            );

            AdVariant::create([
                'ad_set_id' => $set->id,
                'position' => $index + 1,
                'image_path' => $relative,
            ]);
        }

        return $this->redirectRoute('ad-sets.review', ['adSet' => $set], navigate: true);
    }

    protected function regionName(): ?string
    {
        if (! $this->regionKey) {
            return null;
        }

        return collect($this->regions)->firstWhere('key', $this->regionKey)['name'] ?? null;
    }

    public function render()
    {
        return view('livewire.ad-sets.create');
    }
}
