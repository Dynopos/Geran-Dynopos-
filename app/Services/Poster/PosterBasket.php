<?php

namespace App\Services\Poster;

use App\Models\PosterJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Poster yang sudah dijana dan menunggu untuk dijadikan iklan.
 *
 * Tanpa ini, peniaga terpaksa muat turun poster kemudian memuat naiknya semula
 * di skrin /buat — dua langkah yang tiada sebab untuk wujud.
 */
class PosterBasket
{
    private const KEY = 'dynoads.bakul-poster';

    /** @return array<int, int> */
    public function ids(): array
    {
        return array_values(array_unique(Session::get(self::KEY, [])));
    }

    /** @return Collection<int, PosterJob> */
    public function jobs(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return collect();
        }

        // Kekalkan turutan peniaga menambahnya, bukan turutan id.
        return PosterJob::whereIn('id', $ids)->get()->sortBy(
            fn (PosterJob $job) => array_search($job->id, $ids, true)
        )->values();
    }

    public function add(int $posterJobId): void
    {
        $max = (int) config('dynoads.creative.max_images');
        $ids = $this->ids();

        if (in_array($posterJobId, $ids, true) || count($ids) >= $max) {
            return;
        }

        $ids[] = $posterJobId;
        Session::put(self::KEY, $ids);
    }

    public function remove(int $posterJobId): void
    {
        Session::put(self::KEY, array_values(array_diff($this->ids(), [$posterJobId])));
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function has(int $posterJobId): bool
    {
        return in_array($posterJobId, $this->ids(), true);
    }
}
