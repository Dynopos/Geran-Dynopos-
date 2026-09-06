<?php

namespace App\Livewire\AdSets;

use App\Exceptions\MetaApiException;
use App\Models\AdSet;
use App\Models\AdVariant;
use App\Services\ImageProcessor;
use App\Services\MetaAdsService;
use Illuminate\Http\Client\ConnectionException;
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

    /**
     * Kotak pilih fail. Atas telefon, galeri selalunya pulangkan satu gambar
     * setiap kali — jadi medan ini dikosongkan setiap kali dan isinya
     * dipindahkan ke $images. Kalau tidak, pilihan kedua MENGGANTIKAN yang
     * pertama dan peniaga hanya dapat satu gambar tanpa tahu kenapa.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $upload = [];

    /** Gambar yang dikumpul setakat ini. @var array<int, TemporaryUploadedFile> */
    public array $images = [];

    public string $problem = '';

    public string $offer = '';

    public string $phone = '';

    /** @var array<int, string> */
    public array $regionKeys = [];

    public int $budgetRm = 37;

    /** @var array<int, array{key:string,name:string}> */
    public array $regions = [];

    public ?string $regionError = null;

    public function mount(MetaAdsService $meta): void
    {
        $this->phone = (string) config('dynoads.meta.wa_phone');
        $this->budgetRm = intdiv((int) config('dynoads.budget.default_daily_sen'), 100);

        try {
            $this->regions = $meta->searchRegions();
        } catch (\Throwable $e) {
            // Senarai negeri datang dari Meta. Tanpa token yang sah ia gagal —
            // katakan sebabnya, jangan biarkan senarai kosong tanpa penjelasan.
            $this->regions = [];
            $this->regionError = $this->safeReason($e);
        }
    }

    /**
     * Sebab kegagalan yang selamat dipapar.
     *
     * Mesej pengecualian HTTP membawa URL penuh yang dipanggil — dan URL Graph
     * API mengandungi ?access_token=. Peraturan mutlak #8: token tidak pernah
     * dipapar, dilog atau ditulis ke mana-mana. Jadi kita tidak pernah
     * memaparkan mesej mentah; kita pulangkan ayat pendek kita sendiri.
     */
    private function safeReason(\Throwable $e): string
    {
        return match (true) {
            $e instanceof MetaApiException => $e->forHuman(),
            $e instanceof ConnectionException => 'Tidak dapat hubungi Meta dari pelayan ini.',
            default => 'Panggilan ke Meta gagal ('.class_basename($e).').',
        };
    }

    /** Kumpul gambar merentas beberapa kali pilih, jangan ganti. */
    public function updatedUpload(): void
    {
        $max = (int) config('dynoads.creative.max_images');

        foreach ($this->upload as $file) {
            if (count($this->images) >= $max) {
                break;
            }

            $this->images[] = $file;
        }

        $this->upload = [];
        $this->resetValidation('images');
    }

    public function removeImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    public function toggleRegion(string $key): void
    {
        $this->regionKeys = in_array($key, $this->regionKeys, true)
            ? array_values(array_diff($this->regionKeys, [$key]))
            : [...$this->regionKeys, $key];
    }

    public function clearRegions(): void
    {
        $this->regionKeys = [];
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
            'regionKeys' => 'array',
            'regionKeys.*' => 'string',
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

    public function totalDailyRm(): int
    {
        return max(count($this->images), 1) * $this->budgetRm;
    }

    public function save(ImageProcessor $processor)
    {
        $this->validate();

        $set = AdSet::create([
            'name' => str($this->offer)->limit(60)->value(),
            'problem' => $this->problem,
            'offer' => $this->offer,
            'phone' => $this->phone,
            'region_keys' => $this->regionKeys,
            'region_names' => $this->regionNames(),
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

    /** @return array<int, string> */
    protected function regionNames(): array
    {
        return collect($this->regions)
            ->whereIn('key', $this->regionKeys)
            ->pluck('name')
            ->all();
    }

    public function render()
    {
        return view('livewire.ad-sets.create');
    }
}
