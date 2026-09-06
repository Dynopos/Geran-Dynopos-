<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Folder yang Laravel tulis ke dalamnya tanpa menciptanya dahulu.
     *
     * AliasLoader (real-time facade) melakukan file_put_contents terus ke
     * framework/cache. Blade pula mati masa config dimuat kalau laluan
     * compiled-nya kosong. Dua-dua meletup dengan ralat yang mengelirukan.
     *
     * Ini penting pada deploy zero-downtime Laravel Forge: `storage` dalam
     * setiap release ialah symlink ke folder shared yang kosong pada mulanya,
     * jadi direktori yang kita commit dalam git terus dipintas.
     */
    private const STORAGE_DIRECTORIES = [
        'framework/cache/data',
        'framework/sessions',
        'framework/views',
        'app/public',
        'logs',
    ];

    public function register(): void
    {
        $this->ensureStorageDirectoriesExist();
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Cipta folder storage yang hilang. is_dir() dilayan oleh stat cache PHP,
     * jadi kos setiap request hampir sifar selepas panggilan pertama.
     */
    private function ensureStorageDirectoriesExist(): void
    {
        foreach (self::STORAGE_DIRECTORIES as $directory) {
            $path = storage_path($directory);

            if (! is_dir($path)) {
                @mkdir($path, 0775, recursive: true);
            }
        }
    }
}
