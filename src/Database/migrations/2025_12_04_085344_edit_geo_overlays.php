<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('geo_overlays', function (Blueprint $table) {
            $table->dropColumn('context_layer');
        });

        Schema::table('geo_overlays', function (Blueprint $table) {
            $table->boolean('browsing_layer')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geo_overlays', function (Blueprint $table) {
            $table->boolean('context_layer')->default(0);
        });

        Schema::table('geo_overlays', function (Blueprint $table) {
            $table->boolean('browsing_layer')->default(0)->change();
        });
    }
};
