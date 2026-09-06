<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poster_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('template');
            $table->json('data');                                 // headline, subline, harga, cta
            $table->string('background_source')->default('stock'); // stock|ai|upload
            $table->string('background_mood')->nullable();
            $table->string('background_path')->nullable();
            $table->string('product_path')->nullable();            // gambar asal peniaga
            $table->string('cutout_path')->nullable();             // selepas latar dibuang
            $table->string('output_path')->nullable();
            $table->string('cache_key')->unique();
            $table->string('status')->default('pending');          // pending|done|failed
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poster_jobs');
    }
};
