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
            // remove cols from initial table
            $table->dropColumn('top_left_lat');
            $table->dropColumn('top_left_lng');
            $table->dropColumn('bottom_right_lat');
            $table->dropColumn('bottom_right_lng');
            
            // add cols to initial table
            $table->string('type', 10)->nullable();
            $table->boolean('browsing_layer')->default(0);
            $table->boolean('context_layer')->default(0);
            $table->json('layer_index')->nullable();
            $table->json('attrs')->default('{}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geo_overlays', function (Blueprint $table) {
            // add the old cols
            $table->float('top_left_lat', 11, 8)->nullable();
            $table->float('top_left_lng', 11, 8)->nullable();
            $table->float('bottom_right_lat', 11, 8)->nullable();
            $table->float('bottom_right_lng', 11, 8)->nullable();
            // drop the new cols
            $table->dropColumn('type');
            $table->dropColumn('browsing_layer');
            $table->dropColumn('context_layer');
            $table->dropColumn('layer_index');
            $table->dropColumn('attrs');
        });
    }
};
