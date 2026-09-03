<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('problem');            // masalah pelanggan
            $table->text('offer');              // tawaran
            $table->string('phone');            // nombor WhatsApp, format 60...
            $table->string('region_key')->nullable();   // null = seluruh Malaysia
            $table->string('region_name')->nullable();
            $table->unsignedInteger('daily_budget_sen');
            $table->string('status')->default('draft'); // draft|created|running|done
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_sets');
    }
};
