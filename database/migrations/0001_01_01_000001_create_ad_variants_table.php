<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_set_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('image_path');
            $table->text('caption')->nullable();

            // Objek Meta. Kosong = belum dibuat di Meta.
            $table->string('meta_image_hash')->nullable();
            $table->string('meta_campaign_id')->nullable()->index();
            $table->string('meta_adset_id')->nullable();
            $table->string('meta_creative_id')->nullable();
            $table->string('meta_ad_id')->nullable();

            $table->string('status')->default('draft'); // draft|paused|active|failed
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['ad_set_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_variants');
    }
};
