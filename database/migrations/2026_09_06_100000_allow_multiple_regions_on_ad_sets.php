<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu set iklan boleh menyasar beberapa negeri sekali gus.
 * Meta menerima geo_locations.regions sebagai senarai, jadi satu kunci sahaja
 * adalah had yang kita reka sendiri, bukan had Meta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_sets', function (Blueprint $table) {
            $table->json('region_keys')->nullable()->after('phone');
            $table->json('region_names')->nullable()->after('region_keys');
        });

        foreach (DB::table('ad_sets')->whereNotNull('region_key')->get() as $row) {
            DB::table('ad_sets')->where('id', $row->id)->update([
                'region_keys' => json_encode([$row->region_key]),
                'region_names' => json_encode(array_filter([$row->region_name])),
            ]);
        }

        Schema::table('ad_sets', function (Blueprint $table) {
            $table->dropColumn(['region_key', 'region_name']);
        });
    }

    public function down(): void
    {
        Schema::table('ad_sets', function (Blueprint $table) {
            $table->string('region_key')->nullable()->after('phone');
            $table->string('region_name')->nullable()->after('region_key');
        });

        foreach (DB::table('ad_sets')->whereNotNull('region_keys')->get() as $row) {
            DB::table('ad_sets')->where('id', $row->id)->update([
                'region_key' => json_decode($row->region_keys, true)[0] ?? null,
                'region_name' => json_decode($row->region_names, true)[0] ?? null,
            ]);
        }

        Schema::table('ad_sets', function (Blueprint $table) {
            $table->dropColumn(['region_keys', 'region_names']);
        });
    }
};
