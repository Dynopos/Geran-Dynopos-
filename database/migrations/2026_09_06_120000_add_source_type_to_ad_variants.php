<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dari mana creative iklan ini datang.
 *
 * Fasa 5 perlu membandingkan prestasi poster berbanding gambar yang peniaga
 * muat naik sendiri (dan kemudian, posting Page sedia ada). Tanpa medan ini,
 * perbandingan itu mustahil selepas fakta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_variants', function (Blueprint $table) {
            // upload = gambar peniaga sendiri; poster = dijana oleh enjin poster;
            // existing_post menyusul pada Fasa 1.
            $table->string('source_type')->default('upload')->after('position');
            $table->foreignId('poster_job_id')->nullable()->after('source_type')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ad_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('poster_job_id');
            $table->dropColumn('source_type');
        });
    }
};
