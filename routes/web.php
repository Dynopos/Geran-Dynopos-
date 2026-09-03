<?php

use App\Livewire\AdSets\Create;
use App\Livewire\AdSets\Dashboard;
use App\Livewire\AdSets\Review;
use App\Livewire\AdSets\Run;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/buat');

Route::get('/buat', Create::class)->name('ad-sets.create');
Route::get('/semak/{adSet}', Review::class)->name('ad-sets.review');
Route::get('/run/{adSet}', Run::class)->name('ad-sets.run');
Route::get('/dashboard/{adSet}', Dashboard::class)->name('ad-sets.dashboard');
