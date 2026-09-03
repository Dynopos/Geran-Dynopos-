<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_set_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                    // create_campaign|run|pause|sync_metrics
            $table->text('reason');                      // kenapa, dalam BM
            $table->json('payload')->nullable();
            $table->string('result')->default('pending'); // pending|ok|failed
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_actions');
    }
};
