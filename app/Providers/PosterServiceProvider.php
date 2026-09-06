<?php

namespace App\Providers;

use App\Services\Poster\Removers\BackgroundRemover;
use App\Services\Poster\Removers\GdRemover;
use App\Services\Poster\Removers\HttpRemover;
use App\Services\Poster\Removers\NullRemover;
use Illuminate\Support\ServiceProvider;

class PosterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pembekal boleh ditukar dari config sahaja — tiada kod perlu diubah.
        $this->app->bind(BackgroundRemover::class, fn () => match (config('dynoads.poster.remover.driver')) {
            'http' => new HttpRemover,
            'none' => new NullRemover,
            default => new GdRemover,
        });
    }
}
