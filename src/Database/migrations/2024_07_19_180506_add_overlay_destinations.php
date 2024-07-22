<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('geo_overlays', function (Blueprint $table) {
            $table->boolean('browsing_layer')->default(0);
            $table->boolean('context_layer')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geo_overlays', function (Blueprint $table) {
            $table->dropColumn('browsing_layer');
            $table->dropColumn('context_layer');
        });
    }
};
