<?php

namespace App\Livewire\Posters;

use App\Services\Poster\PosterBasket;
use App\Services\Poster\PosterService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Buat poster')]
class Create extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $product = null;

    public string $kicker = '';

    public string $headline = '';

    public string $subline = '';

    public string $price = '';

    public string $cta = 'WhatsApp kami';

    public string $industry = '';

    public string $mood = 'kedai';

    public string $backgroundSource = 'stock';

    public ?int $posterJobId = null;

    public ?string $posterUrl = null;

    public bool $cutoutUsed = false;

    public bool $adaProduk = false;

    public ?string $error = null;

    protected function rules(): array
    {
        return [
            'product' => 'nullable|image|max:8192',
            'kicker' => 'nullable|string|max:24',
            'headline' => 'required|string|min:5|max:90',
            'subline' => 'nullable|string|max:120',
            'price' => 'nullable|string|max:16',
            'cta' => 'required|string|max:24',
            'industry' => 'nullable|string|max:60',
            'mood' => 'required|string',
            'backgroundSource' => 'required|in:stock,ai',
        ];
    }

    protected function messages(): array
    {
        return [
            'headline.required' => 'Tulis ayat utama poster.',
            'headline.max' => 'Ayat utama terlalu panjang — pendekkan supaya senang dibaca.',
            'product.image' => 'Fail kena gambar (JPG, PNG atau WEBP).',
        ];
    }

    public function moods(): array
    {
        return config('dynoads.poster.background.moods');
    }

    public function generate(PosterService $poster): void
    {
        $this->validate();
        $this->reset('error', 'posterUrl');

        try {
            $job = $poster->render(
                template: 'promo-meletup',
                data: [
                    'kicker' => $this->kicker,
                    'headline' => $this->headline,
                    'subline' => $this->subline,
                    'price' => $this->price,
                    'cta' => $this->cta,
                    'industry' => $this->industry,
                ],
                backgroundSource: $this->backgroundSource,
                mood: $this->mood,
                productAbsolutePath: $this->product?->getRealPath(),
            );

            $this->posterJobId = $job->id;
            $this->posterUrl = Storage::disk(config('dynoads.poster.disk'))->url($job->output_path);
            $this->cutoutUsed = filled($job->cutout_path);
            $this->adaProduk = (bool) $this->product;
        } catch (\Throwable $e) {
            $this->error = 'Poster tidak dapat dihasilkan: '.$e->getMessage();
        }
    }

    /** Simpan poster ni untuk dijadikan iklan, tanpa muat turun. */
    public function useForAd(PosterBasket $basket): void
    {
        if (! $this->posterJobId) {
            return;
        }

        $basket->add($this->posterJobId);
    }

    public function removeFromBasket(int $posterJobId, PosterBasket $basket): void
    {
        $basket->remove($posterJobId);
    }

    public function render(PosterBasket $basket)
    {
        return view('livewire.posters.create', [
            'bakul' => $basket->jobs(),
            'sudahDalamBakul' => $this->posterJobId ? $basket->has($this->posterJobId) : false,
            'bakulPenuh' => $basket->count() >= (int) config('dynoads.creative.max_images'),
        ]);
    }
}
